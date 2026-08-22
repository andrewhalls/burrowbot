<?php

declare(strict_types=1);

namespace App\Actions\Broadcasts;

use App\Models\Broadcast;

/**
 * Archives a broadcast series from any status: forces it to `cancelled`
 * (stopping future occurrence generation, same effect as a manual Cancel)
 * and marks it archived, which hides it from the default broadcast list.
 *
 * See openspec specs/broadcasts - "Archiving a broadcast series".
 */
class ArchiveBroadcastAction
{
    public function execute(Broadcast $broadcast): Broadcast
    {
        $broadcast->update([
            'status' => Broadcast::STATUS_CANCELLED,
            'archived_at' => now(),
        ]);

        return $broadcast;
    }
}
