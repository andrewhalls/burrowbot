<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Models\StandardGiveawayOccurrence;
use InvalidArgumentException;

/**
 * Edits a single not-yet-posted occurrence's description and prize items,
 * independent of its series and every other occurrence - both are plain
 * columns on the occurrence (prize_item_ids is a JSON-cast array, not a
 * relation), so this is a direct overwrite, not a re-sync. Refuses once
 * the occurrence is no longer `scheduled`, so what already went to
 * Discord for it never changes after the fact.
 *
 * See openspec specs/standard-giveaway-occurrences - "Editing a single
 * upcoming occurrence".
 */
class UpdateStandardGiveawayOccurrenceAction
{
    /**
     * @param  array{description?: string, prize_item_ids?: list<int>}  $attributes
     */
    public function execute(StandardGiveawayOccurrence $occurrence, array $attributes): StandardGiveawayOccurrence
    {
        if ($occurrence->status !== StandardGiveawayOccurrence::STATUS_SCHEDULED) {
            throw new InvalidArgumentException('Only a scheduled occurrence can be edited.');
        }

        $occurrence->fill(array_intersect_key($attributes, array_flip(['description', 'prize_item_ids'])))->save();

        return $occurrence;
    }
}
