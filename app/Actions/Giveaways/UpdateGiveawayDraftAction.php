<?php

declare(strict_types=1);

namespace App\Actions\Giveaways;

use App\Models\Giveaway;
use InvalidArgumentException;

/**
 * Edits a still-`draft` giveaway's fields. Refuses once the giveaway has
 * left `draft`, per `giveaway-lifecycle` - "Giveaway configuration
 * immutability once started".
 */
class UpdateGiveawayDraftAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Giveaway $giveaway, array $attributes): Giveaway
    {
        if (! $giveaway->isDraft()) {
            throw new InvalidArgumentException('Only a draft giveaway can be edited.');
        }

        $giveaway->update($attributes);

        return $giveaway;
    }
}
