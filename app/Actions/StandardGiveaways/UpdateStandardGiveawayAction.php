<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Support\Facades\Storage;

/**
 * Edits a standard giveaway series. Existing `standard_giveaway_occurrences`
 * rows are snapshotted and never mutated by this action - only occurrences
 * generated after this edit reflect the new values.
 *
 * See openspec specs/standard-giveaways - "Editing a standard giveaway
 * series only affects future occurrences".
 */
class UpdateStandardGiveawayAction
{
    public function execute(StandardGiveaway $giveaway, array $attributes): StandardGiveaway
    {
        $attributes = array_intersect_key(
            $attributes,
            array_flip([
                'title', 'description', 'channel_id', 'posting_mode', 'winner_count',
                'requires_booster', 'duration_minutes', 'image_path',
                'recurrence_rule', 'recurrence_start_at', 'recurrence_timezone',
            ]),
        );

        // The old path is only safe to delete from disk once nothing else
        // still points at it - an already-generated occurrence snapshots
        // the same image_path string (not its own copy of the file), so
        // deleting unconditionally here would break "occurrences generated
        // before the change keep their original image" the moment a series
        // image is replaced (design.md Decision 2, revised during
        // implementation).
        $oldPath = $giveaway->image_path;
        if (
            array_key_exists('image_path', $attributes)
            && $oldPath !== null
            && $oldPath !== $attributes['image_path']
            && ! StandardGiveawayOccurrence::query()->where('image_path', $oldPath)->exists()
        ) {
            Storage::disk('public')->delete($oldPath);
        }

        $giveaway->fill($attributes)->save();

        return $giveaway;
    }
}
