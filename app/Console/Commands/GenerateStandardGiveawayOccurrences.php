<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use App\Support\Events\ExpandRecurrenceRule;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * For every active recurring standard giveaway, expands its recurrence
 * rule up to a rolling window and creates any missing
 * `standard_giveaway_occurrences` rows, snapshotting the giveaway's
 * current values including its current prize items/required roles
 * (design.md Decision 2 and Risks).
 *
 * See openspec specs/standard-giveaway-occurrences - "Occurrence
 * generation for recurring standard giveaways".
 */
#[Signature('standard-giveaways:generate-occurrences')]
#[Description('Generate upcoming occurrences for active recurring standard giveaways within a rolling window.')]
class GenerateStandardGiveawayOccurrences extends Command
{
    private const WINDOW_DAYS = 30;

    public function handle(ExpandRecurrenceRule $expandRecurrenceRule): int
    {
        $windowStart = now();
        $windowEnd = now()->addDays(self::WINDOW_DAYS);

        $giveaways = StandardGiveaway::query()
            ->where('status', StandardGiveaway::STATUS_ACTIVE)
            ->whereNotNull('recurrence_rule')
            ->with('prizeItems', 'requiredRoles')
            ->get();

        $created = 0;

        foreach ($giveaways as $giveaway) {
            $startTimes = $expandRecurrenceRule(
                $giveaway->recurrence_rule,
                $giveaway->recurrence_start_at,
                $giveaway->recurrence_timezone ?? 'UTC',
                $windowStart,
                $windowEnd,
            );

            foreach ($startTimes as $startAt) {
                $occurrence = StandardGiveawayOccurrence::query()->firstOrCreate(
                    ['standard_giveaway_id' => $giveaway->id, 'scheduled_post_at' => $startAt],
                    [
                        'title' => $giveaway->title,
                        'description' => $giveaway->description,
                        'channel_id' => $giveaway->channel_id,
                        'posting_mode' => $giveaway->posting_mode,
                        'requires_booster' => $giveaway->requires_booster,
                        'winner_count' => $giveaway->winner_count,
                        'duration_minutes' => $giveaway->duration_minutes,
                        'prize_item_ids' => $giveaway->prizeItems->pluck('collection_theme_item_id')->all(),
                        'required_role_ids' => $giveaway->requiredRoles->pluck('discord_role_id')->all(),
                        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
                    ],
                );

                if ($occurrence->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        $this->info("Generated {$created} new occurrence(s).");

        return self::SUCCESS;
    }
}
