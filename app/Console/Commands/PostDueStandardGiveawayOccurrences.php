<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CollectionThemeItem;
use App\Models\DiscordOutboundAction;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Finds `scheduled` standard giveaway occurrences and enqueues the
 * outbound action that posts them to Discord, stamping `posted_at` and
 * computing `ends_at = posted_at + duration_minutes` (design.md
 * Decision 2) - v1 posts every scheduled occurrence immediately, same
 * default as the Events equivalent.
 *
 * See openspec specs/standard-giveaway-occurrences - "Posting an
 * occurrence to Discord".
 */
#[Signature('standard-giveaways:post-due-occurrences')]
#[Description('Enqueue the outbound action to post each scheduled standard giveaway occurrence that is due.')]
class PostDueStandardGiveawayOccurrences extends Command
{
    public function handle(): int
    {
        $occurrences = StandardGiveawayOccurrence::query()
            ->where('status', StandardGiveawayOccurrence::STATUS_SCHEDULED)
            ->get();

        foreach ($occurrences as $occurrence) {
            DB::transaction(function () use ($occurrence) {
                $postedAt = now();

                DiscordOutboundAction::query()->firstOrCreate([
                    'standard_giveaway_occurrence_id' => $occurrence->id,
                    'type' => $occurrence->isThreadMode()
                        ? DiscordOutboundAction::TYPE_POST_STANDARD_GIVEAWAY_THREAD
                        : DiscordOutboundAction::TYPE_POST_STANDARD_GIVEAWAY_MESSAGE,
                ], [
                    'payload' => [
                        'occurrence_id' => $occurrence->id,
                        'channel_id' => $occurrence->channel_id,
                        'title' => $occurrence->title,
                        'description' => $occurrence->description,
                        'ends_at' => $postedAt->clone()->addMinutes($occurrence->duration_minutes)->toIso8601String(),
                        'requires_booster' => $occurrence->requires_booster,
                        'required_role_ids' => $occurrence->required_role_ids,
                        'prize_item_names' => CollectionThemeItem::query()
                            ->whereIn('id', $occurrence->prize_item_ids)
                            ->pluck('name')
                            ->all(),
                    ],
                    'status' => DiscordOutboundAction::STATUS_PENDING,
                ]);

                $occurrence->update([
                    'status' => StandardGiveawayOccurrence::STATUS_POSTED,
                    'posted_at' => $postedAt,
                    'ends_at' => $postedAt->clone()->addMinutes($occurrence->duration_minutes),
                ]);
            });
        }

        $this->info("Posted {$occurrences->count()} occurrence(s).");

        return self::SUCCESS;
    }
}
