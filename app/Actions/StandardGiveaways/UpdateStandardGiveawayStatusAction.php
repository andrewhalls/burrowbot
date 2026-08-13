<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Models\StandardGiveaway;
use InvalidArgumentException;

/**
 * Transitions a standard giveaway series between active/paused/cancelled.
 * Pausing or cancelling stops future occurrence generation without
 * touching occurrences already generated.
 *
 * See openspec specs/standard-giveaways - "Standard giveaway series status".
 */
class UpdateStandardGiveawayStatusAction
{
    private const VALID_STATUSES = [
        StandardGiveaway::STATUS_ACTIVE,
        StandardGiveaway::STATUS_PAUSED,
        StandardGiveaway::STATUS_CANCELLED,
    ];

    public function execute(StandardGiveaway $giveaway, string $status): StandardGiveaway
    {
        if (! in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid standard giveaway status: {$status}");
        }

        $giveaway->update(['status' => $status]);

        return $giveaway;
    }
}
