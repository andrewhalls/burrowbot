<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Models\StandardGiveaway;

/**
 * Archives a standard giveaway series from any status: forces it to
 * `cancelled` (stopping future occurrence generation, same effect as a
 * manual Cancel) and marks it archived, which hides it from the default
 * standard giveaway list.
 *
 * See openspec specs/standard-giveaways - "Archiving a standard giveaway
 * series".
 */
class ArchiveStandardGiveawayAction
{
    public function execute(StandardGiveaway $giveaway): StandardGiveaway
    {
        $giveaway->update([
            'status' => StandardGiveaway::STATUS_CANCELLED,
            'archived_at' => now(),
        ]);

        return $giveaway;
    }
}
