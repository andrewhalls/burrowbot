<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Event;

/**
 * Unarchives an event series: clears only the archived marker, so it shows
 * up in the default event list again. Leaves `status` untouched - an
 * archived series stays `cancelled`; the admin can separately reactivate
 * it via the existing Activate action if they want it live again.
 *
 * See openspec specs/events - "Archiving an event series".
 */
class UnarchiveEventAction
{
    public function execute(Event $event): Event
    {
        $event->update(['archived_at' => null]);

        return $event;
    }
}
