<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates an event series. A one-off event (no recurrence rule) also gets
 * its single occurrence created immediately, snapshotted from the event.
 * A recurring event's occurrences are left to the generation job (see
 * Support\Events\ExpandRecurrenceRule and the events:generate-occurrences
 * command).
 *
 * See openspec specs/events - "Event creation";
 * specs/event-occurrences - "One-off events generate exactly one occurrence".
 */
class CreateEventAction
{
    public function execute(
        Guild $guild,
        EventRoleSet $roleSet,
        string $title,
        string $description,
        string $channelId,
        string $postingMode,
        ?string $recurrenceRule,
        ?\DateTimeInterface $recurrenceStartAt,
        ?string $recurrenceTimezone,
        ?string $imagePath = null,
        ?User $createdBy = null,
    ): Event {
        return DB::transaction(function () use (
            $guild, $roleSet, $title, $description, $channelId, $postingMode,
            $recurrenceRule, $recurrenceStartAt, $recurrenceTimezone, $imagePath, $createdBy,
        ) {
            $event = $guild->events()->create([
                'created_by_user_id' => $createdBy?->id,
                'event_role_set_id' => $roleSet->id,
                'title' => $title,
                'description' => $description,
                'image_path' => $imagePath,
                'channel_id' => $channelId,
                'posting_mode' => $postingMode,
                'status' => Event::STATUS_ACTIVE,
                'recurrence_rule' => $recurrenceRule,
                'recurrence_start_at' => $recurrenceStartAt,
                'recurrence_timezone' => $recurrenceTimezone,
            ]);

            if (! $event->isRecurring() && $recurrenceStartAt !== null) {
                $event->occurrences()->create([
                    'title' => $event->title,
                    'description' => $event->description,
                    'image_path' => $event->image_path,
                    'channel_id' => $event->channel_id,
                    'posting_mode' => $event->posting_mode,
                    'event_role_set_id' => $event->event_role_set_id,
                    'scheduled_start_at' => $recurrenceStartAt,
                    'status' => EventOccurrence::STATUS_SCHEDULED,
                ]);
            }

            return $event;
        });
    }
}
