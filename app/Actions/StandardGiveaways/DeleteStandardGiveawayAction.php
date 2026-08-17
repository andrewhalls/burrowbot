<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Models\StandardGiveaway;
use InvalidArgumentException;

/**
 * Permanently deletes a standard giveaway series, as long as none of its
 * occurrences have reached Discord (posted or closed) - refuses otherwise,
 * so an already-posted Discord message is never left orphaned by a
 * delete. Cascading foreign keys handle removing any still-`scheduled`
 * occurrences (and their prize items/required roles) along with it.
 *
 * See openspec specs/standard-giveaways - "Deleting a standard giveaway series".
 */
class DeleteStandardGiveawayAction
{
    public function execute(StandardGiveaway $giveaway): void
    {
        if (! $giveaway->isDeletable()) {
            throw new InvalidArgumentException('Only a standard giveaway with no posted or closed occurrences can be deleted.');
        }

        $giveaway->delete();
    }
}
