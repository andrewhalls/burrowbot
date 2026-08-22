<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Broadcast;
use App\Models\BroadcastOccurrence;
use App\Support\Events\ExpandRecurrenceRule;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * For every active recurring broadcast, expands its recurrence rule up to
 * a rolling window and creates any missing `broadcast_occurrences` rows,
 * snapshotting the broadcast's current message_template/channel_id.
 * Reuses the exact same rolling-window RRULE expansion as
 * events:generate-occurrences/standard-giveaways:generate-occurrences
 * (design.md Decision 3).
 *
 * See openspec specs/broadcast-occurrences - "Occurrence generation for
 * recurring broadcasts".
 */
#[Signature('broadcasts:generate-occurrences')]
#[Description('Generate upcoming occurrences for active recurring broadcasts within a rolling window.')]
class GenerateBroadcastOccurrences extends Command
{
    private const WINDOW_DAYS = 90;

    public function handle(ExpandRecurrenceRule $expandRecurrenceRule): int
    {
        $windowStart = now();
        $windowEnd = now()->addDays(self::WINDOW_DAYS);

        $broadcasts = Broadcast::query()
            ->where('status', Broadcast::STATUS_ACTIVE)
            ->whereNotNull('recurrence_rule')
            ->get();

        $created = 0;

        foreach ($broadcasts as $broadcast) {
            $postTimes = $expandRecurrenceRule(
                $broadcast->recurrence_rule,
                $broadcast->recurrence_start_at,
                $broadcast->recurrence_timezone ?? 'UTC',
                $windowStart,
                $windowEnd,
            );

            foreach ($postTimes as $postAt) {
                // See GenerateEventOccurrences for why this must be
                // converted to a true UTC instant before it's persisted -
                // scheduled_post_at is compared directly against now().
                $postAt = $postAt->clone()->utc();

                $occurrence = BroadcastOccurrence::query()->firstOrCreate(
                    ['broadcast_id' => $broadcast->id, 'scheduled_post_at' => $postAt],
                    [
                        'message_template' => $broadcast->message_template,
                        'channel_id' => $broadcast->channel_id,
                        'status' => BroadcastOccurrence::STATUS_SCHEDULED,
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
