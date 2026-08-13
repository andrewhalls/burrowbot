<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Event;
use InvalidArgumentException;

/**
 * Transitions an event series between active/paused/cancelled. Pausing or
 * cancelling stops future occurrence generation without touching
 * occurrences already generated.
 *
 * See openspec specs/events - "Event series status".
 */
class UpdateEventStatusAction
{
    private const VALID_STATUSES = [Event::STATUS_ACTIVE, Event::STATUS_PAUSED, Event::STATUS_CANCELLED];

    public function execute(Event $event, string $status): Event
    {
        if (! in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid event status: {$status}");
        }

        $event->update(['status' => $status]);

        return $event;
    }
}
