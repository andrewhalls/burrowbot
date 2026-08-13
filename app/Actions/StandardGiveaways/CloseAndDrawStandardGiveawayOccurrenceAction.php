<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Models\CollectionThemeItem;
use App\Models\DiscordMember;
use App\Models\DiscordOutboundAction;
use App\Models\StandardGiveawayOccurrence;
use App\Models\StandardGiveawayWinner;
use App\Support\Giveaways\AssignRandomItem;
use App\Support\StandardGiveaways\DrawRandomWinners;
use Illuminate\Support\Facades\DB;

/**
 * Closes a `posted` occurrence past its `ends_at` and draws its winners:
 * locks the occurrence row (mirroring JoinGiveawayAction/
 * SignUpForEventRoleAction), draws the configured number of winners from
 * eligible entrants, assigns each a prize item via the existing
 * AssignRandomItem (accumulating across this occurrence's winners only),
 * and enqueues the outbound action to announce them.
 *
 * See openspec specs/standard-giveaway-occurrences - "Automatic closing
 * and drawing at end time", "Fair prize assignment across multiple
 * winners"; design.md Decision 3.
 */
class CloseAndDrawStandardGiveawayOccurrenceAction
{
    public function __construct(
        private readonly DrawRandomWinners $drawRandomWinners,
        private readonly AssignRandomItem $assignRandomItem,
    ) {}

    public function execute(StandardGiveawayOccurrence $occurrence): StandardGiveawayOccurrence
    {
        return DB::transaction(function () use ($occurrence) {
            $locked = StandardGiveawayOccurrence::query()->lockForUpdate()->findOrFail($occurrence->id);

            // Idempotent: a second call (e.g. the scheduled command seeing
            // the same row across two ticks) is a no-op.
            if (! $locked->isPosted() || ! $locked->hasEnded()) {
                return $locked;
            }

            $locked->update(['status' => StandardGiveawayOccurrence::STATUS_CLOSED]);

            $entries = $locked->entries()->with('discordMember')->get();
            $drawnEntryIds = ($this->drawRandomWinners)($entries->pluck('id')->all(), $locked->winner_count);

            $allItemIds = $locked->prize_item_ids;
            $itemNames = CollectionThemeItem::query()->whereIn('id', $allItemIds)->pluck('name', 'id');
            $wonItemIds = [];
            $drawnAt = now();
            $announcedWinners = [];

            foreach ($drawnEntryIds as $entryId) {
                $unwonItemIds = array_values(array_diff($allItemIds, $wonItemIds));
                $itemId = ($this->assignRandomItem)($unwonItemIds, $allItemIds);
                $wonItemIds[] = $itemId;

                StandardGiveawayWinner::query()->create([
                    'standard_giveaway_occurrence_id' => $locked->id,
                    'standard_giveaway_entry_id' => $entryId,
                    'collection_theme_item_id' => $itemId,
                    'drawn_at' => $drawnAt,
                ]);

                /** @var DiscordMember $member */
                $member = $entries->firstWhere('id', $entryId)->discordMember;

                $announcedWinners[] = [
                    'discord_user_id' => $member->discord_user_id,
                    'username' => $member->username,
                    'item_id' => $itemId,
                    'item_name' => $itemNames[$itemId] ?? null,
                ];
            }

            DiscordOutboundAction::query()->create([
                'type' => DiscordOutboundAction::TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS,
                'standard_giveaway_occurrence_id' => $locked->id,
                'payload' => [
                    'occurrence_id' => $locked->id,
                    'channel_id' => $locked->channel_id,
                    'discord_thread_id' => $locked->discord_thread_id,
                    'discord_message_id' => $locked->discord_message_id,
                    'winners' => $announcedWinners,
                ],
                'status' => DiscordOutboundAction::STATUS_PENDING,
            ]);

            return $locked;
        });
    }
}
