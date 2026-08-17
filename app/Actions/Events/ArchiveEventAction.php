<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Event;

/**
 * Archives an event series from any status: forces it to `cancelled`
 * (stopping future occurrence generation, same effect as a manual Cancel)
 * and marks it archived, which hides it from the default event list.
 *
 * See openspec specs/events - "Archiving an event series".
 */
class ArchiveEventAction
{
    public function execute(Event $event): Event
    {
        $event->update([
            'status' => Event::STATUS_CANCELLED,
            'archived_at' => now(),
        ]);

        return $event;
    }
}
