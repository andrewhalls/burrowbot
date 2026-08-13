<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Models\StandardGiveaway;

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
        $giveaway->fill(array_intersect_key(
            $attributes,
            array_flip([
                'title', 'description', 'channel_id', 'posting_mode', 'winner_count',
                'requires_booster', 'duration_minutes',
                'recurrence_rule', 'recurrence_start_at', 'recurrence_timezone',
            ]),
        ))->save();

        return $giveaway;
    }
}
