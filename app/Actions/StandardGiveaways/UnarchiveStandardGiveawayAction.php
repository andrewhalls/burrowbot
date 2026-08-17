<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Models\StandardGiveaway;

/**
 * Unarchives a standard giveaway series: clears only the archived marker,
 * so it shows up in the default standard giveaway list again. Leaves
 * `status` untouched - an archived series stays `cancelled`; the admin
 * can separately reactivate it via the existing Activate action if they
 * want it live again.
 *
 * See openspec specs/standard-giveaways - "Archiving a standard giveaway
 * series".
 */
class UnarchiveStandardGiveawayAction
{
    public function execute(StandardGiveaway $giveaway): StandardGiveaway
    {
        $giveaway->update(['archived_at' => null]);

        return $giveaway;
    }
}
