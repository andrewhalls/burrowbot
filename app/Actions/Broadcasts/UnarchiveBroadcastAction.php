<?php

declare(strict_types=1);

namespace App\Actions\Broadcasts;

use App\Models\Broadcast;

/**
 * Unarchives a broadcast series: clears only the archived marker, so it
 * shows up in the default broadcast list again. Leaves `status` untouched -
 * an archived series stays `cancelled`; the admin can separately reactivate
 * it via the existing Activate action if they want it live again.
 *
 * See openspec specs/broadcasts - "Archiving a broadcast series".
 */
class UnarchiveBroadcastAction
{
    public function execute(Broadcast $broadcast): Broadcast
    {
        $broadcast->update(['archived_at' => null]);

        return $broadcast;
    }
}
