<?php

declare(strict_types=1);

namespace App\Actions\Giveaways;

use App\Actions\Members\SyncDiscordMemberAction;
use App\Models\DiscordOutboundAction;
use App\Models\Giveaway;
use App\Models\GiveawayEntry;
use App\Support\Giveaways\AssignRandomItem;
use App\Support\Giveaways\JoinResult;
use App\Support\Giveaways\RenderWinnerMessage;
use Illuminate\Support\Facades\DB;

/**
 * Processes a "Join Giveaway" click: the single place that enforces one
 * entry per member, authoritative expiry, and fair random assignment. Also
 * the single place a win happens, so it's where the optional per-winner
 * templated message (giveaway-entry - "Per-winner templated message sent
 * on a new win") is enqueued, immediately after the winning entry is
 * created and never for a duplicate/expired join.
 *
 * See openspec specs/giveaway-entry (all requirements) and design.md §3-4.
 */
class JoinGiveawayAction
{
    public function __construct(
        private readonly SyncDiscordMemberAction $syncMember,
        private readonly AssignRandomItem $assignRandomItem,
        private readonly RenderWinnerMessage $renderWinnerMessage,
    ) {}

    public function execute(Giveaway $giveaway, string $discordUserId, string $discordUsername, ?string $discordDisplayName = null): JoinResult
    {
        return DB::transaction(function () use ($giveaway, $discordUserId, $discordUsername, $discordDisplayName) {
            // Locks the giveaway row so concurrent joins on the SAME
            // giveaway serialize here - this is what makes "one entry per
            // member" and "no item awarded twice" race-safe, not just the
            // unique constraint (which remains a defense-in-depth backstop).
            $locked = Giveaway::query()->lockForUpdate()->findOrFail($giveaway->id);

            $member = $this->syncMember->execute($locked->guild, $discordUserId, $discordUsername, displayName: $discordDisplayName);

            // Authoritative expiry check: independent of whether the
            // scheduled `giveaways:close-expired` job has flipped `status`
            // to `closed` yet (design.md §4).
            if (! $locked->isActive() || $locked->hasExpired()) {
                return JoinResult::expired();
            }

            $existing = GiveawayEntry::query()
                ->where('giveaway_id', $locked->id)
                ->where('discord_member_id', $member->id)
                ->first();

            if ($existing) {
                return JoinResult::alreadyEntered($existing->collectionThemeItem);
            }

            $allItemIds = $locked->collectionTheme->items()->pluck('id')->all();

            $wonItemIds = GiveawayEntry::query()
                ->where('giveaway_id', $locked->id)
                ->whereNotNull('collection_theme_item_id')
                ->pluck('collection_theme_item_id')
                ->all();

            $unwonItemIds = array_values(array_diff($allItemIds, $wonItemIds));

            $itemId = ($this->assignRandomItem)($unwonItemIds, $allItemIds);

            $entry = GiveawayEntry::query()->create([
                'giveaway_id' => $locked->id,
                'discord_member_id' => $member->id,
                'collection_theme_item_id' => $itemId,
            ]);

            if ($locked->hasWinnerMessageConfigured() && $locked->guild->popup_giveaway_winner_messages_enabled) {
                DiscordOutboundAction::query()->create([
                    'type' => DiscordOutboundAction::TYPE_ANNOUNCE_GIVEAWAY_WINNER,
                    'giveaway_id' => $locked->id,
                    'payload' => [
                        'channel_id' => $locked->winner_message_channel_id,
                        'message' => ($this->renderWinnerMessage)(
                            $locked->winner_message_template,
                            $discordUserId,
                            $entry->collectionThemeItem->name,
                        ),
                    ],
                    'status' => DiscordOutboundAction::STATUS_PENDING,
                ]);
            }

            return JoinResult::won($entry->collectionThemeItem);
        });
    }
}
