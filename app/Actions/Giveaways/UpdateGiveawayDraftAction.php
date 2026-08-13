<?php

declare(strict_types=1);

namespace App\Actions\Giveaways;

use App\Models\Giveaway;
use InvalidArgumentException;

/**
 * Updates a giveaway's channel/collection-theme/duration. Only permitted
 * while the giveaway is still `draft` - see openspec specs/giveaway-lifecycle
 * - "Giveaway configuration immutability once started".
 */
class UpdateGiveawayDraftAction
{
    public function execute(Giveaway $giveaway, array $attributes): Giveaway
    {
        if (! $giveaway->isDraft()) {
            throw new InvalidArgumentException('A giveaway can only be edited while it is still a draft.');
        }

        $giveaway->fill(array_intersect_key(
            $attributes,
            array_flip(['collection_theme_id', 'channel_id', 'duration_minutes']),
        ))->save();

        return $giveaway;
    }
}
