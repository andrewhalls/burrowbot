<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventOccurrence;
use App\Support\Events\ExpandRecurrenceRule;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * For every active recurring event, expands its recurrence rule up to a
 * rolling window and creates any missing `event_occurrences` rows,
 * snapshotting the event's current values (design.md Decision 2-3).
 *
 * See openspec specs/event-occurrences - "Occurrence generation for
 * recurring events".
 */
#[Signature('events:generate-occurrences')]
#[Description('Generate upcoming occurrences for active recurring events within a rolling window.')]
class GenerateEventOccurrences extends Command
{
    private const WINDOW_DAYS = 90;

    public function handle(ExpandRecurrenceRule $expandRecurrenceRule): int
    {
        $windowStart = now();
        $windowEnd = now()->addDays(self::WINDOW_DAYS);

        $events = Event::query()
            ->where('status', Event::STATUS_ACTIVE)
            ->whereNotNull('recurrence_rule')
            ->get();

        $created = 0;

        foreach ($events as $event) {
            $startTimes = $expandRecurrenceRule(
                $event->recurrence_rule,
                $event->recurrence_start_at,
                $event->recurrence_timezone ?? 'UTC',
                $windowStart,
                $windowEnd,
            );

            foreach ($startTimes as $startAt) {
                $occurrence = EventOccurrence::query()->firstOrCreate(
                    ['event_id' => $event->id, 'scheduled_start_at' => $startAt],
                    [
                        'title' => $event->title,
                        'description' => $event->description,
                        'image_path' => $event->image_path,
                        'channel_id' => $event->channel_id,
                        'posting_mode' => $event->posting_mode,
                        'event_role_set_id' => $event->event_role_set_id,
                        'status' => EventOccurrence::STATUS_SCHEDULED,
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
