<?php

declare(strict_types=1);

namespace App\Actions\Broadcasts;

use App\Models\Broadcast;
use InvalidArgumentException;

/**
 * Permanently deletes a broadcast series, as long as none of its
 * occurrences have been posted to Discord - refuses otherwise, so an
 * already-posted Discord message is never left orphaned by a delete.
 * Cascading foreign keys handle removing any still-`scheduled` occurrences
 * along with it.
 *
 * See openspec specs/broadcasts - "Deleting a broadcast series".
 */
class DeleteBroadcastAction
{
    public function execute(Broadcast $broadcast): void
    {
        if (! $broadcast->isDeletable()) {
            throw new InvalidArgumentException('Only a broadcast series with no posted occurrences can be deleted.');
        }

        $broadcast->delete();
    }
}
