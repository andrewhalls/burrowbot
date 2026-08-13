<?php

declare(strict_types=1);

namespace App\Actions\Giveaways;

use App\Jobs\PostGiveawayMessage;
use App\Models\Giveaway;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

/**
 * Transitions a draft giveaway to active: fixes its close time and
 * requests the bot post it to Discord.
 *
 * See openspec specs/giveaway-lifecycle - "Starting a giveaway".
 */
class StartGiveawayAction
{
    public function execute(Giveaway $giveaway): Giveaway
    {
        if (! $giveaway->isDraft()) {
            throw new InvalidArgumentException('Only a draft giveaway can be started.');
        }

        $startedAt = now();

        $giveaway->fill([
            'status' => Giveaway::STATUS_ACTIVE,
            'starts_at' => $startedAt,
            'ends_at' => $startedAt->clone()->addMinutes($giveaway->duration_minutes),
        ])->save();

        PostGiveawayMessage::dispatch($giveaway)->onQueue(Config::string('discord.outbound_queue'));

        return $giveaway;
    }
}
