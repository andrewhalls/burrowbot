<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Event;
use InvalidArgumentException;

/**
 * Permanently deletes an event series, as long as none of its occurrences
 * have been posted to Discord - refuses otherwise, so an already-posted
 * Discord message is never left orphaned by a delete. Cascading foreign
 * keys handle removing any still-`scheduled` occurrences along with it.
 *
 * See openspec specs/events - "Deleting an event series".
 */
class DeleteEventAction
{
    public function execute(Event $event): void
    {
        if (! $event->isDeletable()) {
            throw new InvalidArgumentException('Only an event series with no posted occurrences can be deleted.');
        }

        $event->delete();
    }
}
