<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Models\StandardGiveawayOccurrence;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Edits a single not-yet-posted occurrence's description, prize items,
 * and image, independent of its series and every other occurrence - all
 * are plain columns on the occurrence (prize_item_ids is a JSON-cast
 * array, not a relation), so this is a direct overwrite, not a re-sync.
 * Refuses once the occurrence is no longer `scheduled`, so what already
 * went to Discord for it never changes after the fact.
 *
 * See openspec specs/standard-giveaway-occurrences - "Editing a single
 * upcoming occurrence".
 */
class UpdateStandardGiveawayOccurrenceAction
{
    /**
     * @param  array{description?: string, prize_item_ids?: list<int>, image_path?: ?string}  $attributes
     */
    public function execute(StandardGiveawayOccurrence $occurrence, array $attributes): StandardGiveawayOccurrence
    {
        if ($occurrence->status !== StandardGiveawayOccurrence::STATUS_SCHEDULED) {
            throw new InvalidArgumentException('Only a scheduled occurrence can be edited.');
        }

        $attributes = array_intersect_key($attributes, array_flip(['description', 'prize_item_ids', 'image_path']));

        // The old path is only safe to delete once nothing else still
        // points at it - the series' own image_path (an occurrence
        // usually still shares it until explicitly overridden, per
        // GenerateStandardGiveawayOccurrences' snapshot) or a sibling
        // occurrence that hasn't diverged either (design.md Decision 3,
        // fix-occurrence-posting-timing - mirrors
        // UpdateStandardGiveawayAction's own image-replace logic).
        $oldPath = $occurrence->image_path;
        if (
            array_key_exists('image_path', $attributes)
            && $oldPath !== null
            && $oldPath !== $attributes['image_path']
            && $occurrence->standardGiveaway->image_path !== $oldPath
            && ! StandardGiveawayOccurrence::query()
                ->where('id', '!=', $occurrence->id)
                ->where('standard_giveaway_id', $occurrence->standard_giveaway_id)
                ->where('image_path', $oldPath)
                ->exists()
        ) {
            Storage::disk('public')->delete($oldPath);
        }

        $occurrence->fill($attributes)->save();

        return $occurrence;
    }
}
