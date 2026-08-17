<?php

declare(strict_types=1);

namespace App\Actions\Giveaways;

use App\Models\Giveaway;
use InvalidArgumentException;

/**
 * Permanently deletes a still-`draft` giveaway. Refuses once the giveaway
 * has left `draft`, so an already-posted Discord message is never left
 * orphaned by a delete - mirrors UpdateGiveawayDraftAction's own rule.
 *
 * See openspec specs/giveaway-lifecycle - "Deleting a draft giveaway".
 */
class DeleteGiveawayDraftAction
{
    public function execute(Giveaway $giveaway): void
    {
        if (! $giveaway->isDraft()) {
            throw new InvalidArgumentException('Only a draft giveaway can be deleted.');
        }

        $giveaway->delete();
    }
}
