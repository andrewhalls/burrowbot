<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Event;

/**
 * Edits an event series. Existing `event_occurrences` rows are
 * snapshotted and are never mutated by this action - only occurrences
 * generated after this edit reflect the new values.
 *
 * See openspec specs/events - "Editing an event series only affects
 * future occurrences".
 */
class UpdateEventAction
{
    public function execute(Event $event, array $attributes): Event
    {
        $event->fill(array_intersect_key(
            $attributes,
            array_flip([
                'title', 'description', 'channel_id', 'posting_mode', 'event_role_set_id',
                'recurrence_rule', 'recurrence_start_at', 'recurrence_timezone',
            ]),
        ))->save();

        return $event;
    }
}
