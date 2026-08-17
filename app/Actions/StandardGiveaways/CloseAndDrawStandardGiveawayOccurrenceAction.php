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
use App\Support\StandardGiveaways\RenderCongratsMessage;
use Illuminate\Support\Facades\DB;

/**
 * Closes a `posted` occurrence past its `ends_at` and draws its winners:
 * locks the occurrence row (mirroring JoinGiveawayAction/
 * SignUpForEventRoleAction), draws the configured number of winners from
 * eligible entrants, assigns each a prize item via the existing
 * AssignRandomItem (accumulating across this occurrence's winners only),
 * and enqueues one outbound action that both edits the original live post
 * (rebuilt from this payload's own snapshot fields) and, when the
 * occurrence has a congrats_message_template and at least one winner,
 * sends a separately-templated congratulations/claim message.
 *
 * See openspec specs/standard-giveaway-occurrences - "Automatic closing
 * and drawing at end time", "Fair prize assignment across multiple
 * winners", "Winners and claim details shown on the closed occurrence",
 * "Separate winner announcement message with claim details"; design.md
 * Decisions 2, 3, 4.
 */
class CloseAndDrawStandardGiveawayOccurrenceAction
{
    public function __construct(
        private readonly DrawRandomWinners $drawRandomWinners,
        private readonly AssignRandomItem $assignRandomItem,
        private readonly RenderCongratsMessage $renderCongratsMessage,
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
            $items = CollectionThemeItem::query()->whereIn('id', $allItemIds)->get()->keyBy('id');
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
                    'display_name' => $member->display_name_or_username,
                    'item_id' => $itemId,
                    'item_name' => $items->get($itemId)?->name,
                    'item_image_url' => $items->get($itemId)?->image_url,
                ];
            }

            $prizeItemNames = $items->pluck('name')->all();

            $claimDeadlineAt = $locked->claim_deadline_hours !== null
                ? now()->addHours($locked->claim_deadline_hours)
                : null;

            $congratsMessage = ($announcedWinners !== [] && $locked->congrats_message_template !== null)
                ? ($this->renderCongratsMessage)(
                    $locked->congrats_message_template,
                    array_column($announcedWinners, 'discord_user_id'),
                    implode(', ', $prizeItemNames),
                    $locked->claim_link,
                    $claimDeadlineAt,
                )
                : null;

            DiscordOutboundAction::query()->create([
                'type' => DiscordOutboundAction::TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS,
                'standard_giveaway_occurrence_id' => $locked->id,
                'payload' => [
                    'occurrence_id' => $locked->id,
                    'title' => $locked->title,
                    'description' => $locked->description,
                    'ends_at' => $locked->ends_at?->toIso8601String(),
                    'image_url' => $locked->image_url,
                    'banner_image_url' => $locked->banner_image_url,
                    'requires_booster' => $locked->requires_booster,
                    'required_role_ids' => $locked->required_role_ids,
                    'prize_item_names' => $prizeItemNames,
                    'channel_id' => $locked->channel_id,
                    'discord_thread_id' => $locked->discord_thread_id,
                    'discord_message_id' => $locked->discord_message_id,
                    'winners' => $announcedWinners,
                    'claim_deadline_at' => $claimDeadlineAt?->toIso8601String(),
                    'congrats_message' => $congratsMessage,
                ],
                'status' => DiscordOutboundAction::STATUS_PENDING,
            ]);

            return $locked;
        });
    }
}
