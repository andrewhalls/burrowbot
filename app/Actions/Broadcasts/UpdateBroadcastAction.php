<?php

declare(strict_types=1);

namespace App\Actions\Broadcasts;

use App\Models\Broadcast;

/**
 * Edits a broadcast series. Existing `broadcast_occurrences` rows are
 * snapshotted and are never mutated by this action - only occurrences
 * generated after this edit reflect the new values.
 *
 * See openspec specs/broadcasts - "Editing a broadcast series only
 * affects future occurrences".
 */
class UpdateBroadcastAction
{
    public function execute(Broadcast $broadcast, array $attributes): Broadcast
    {
        $attributes = array_intersect_key(
            $attributes,
            array_flip([
                'title', 'message_template', 'channel_id',
                'recurrence_rule', 'recurrence_start_at', 'recurrence_timezone',
            ]),
        );

        $broadcast->fill($attributes)->save();

        return $broadcast;
    }
}
