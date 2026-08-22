<?php

declare(strict_types=1);

namespace App\Actions\Broadcasts;

use App\Models\Broadcast;
use InvalidArgumentException;

/**
 * Transitions a broadcast series between active/paused/cancelled. Pausing
 * or cancelling stops future occurrence generation without touching
 * occurrences already generated.
 *
 * See openspec specs/broadcasts - "Broadcast series status".
 */
class UpdateBroadcastStatusAction
{
    private const VALID_STATUSES = [Broadcast::STATUS_ACTIVE, Broadcast::STATUS_PAUSED, Broadcast::STATUS_CANCELLED];

    public function execute(Broadcast $broadcast, string $status): Broadcast
    {
        if (! in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid broadcast status: {$status}");
        }

        $broadcast->update(['status' => $status]);

        return $broadcast;
    }
}
