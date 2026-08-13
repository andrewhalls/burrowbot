<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DiscordOutboundAction;
use App\Models\Giveaway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Enqueues a "this giveaway has closed, edit the Discord message" outbound
 * action for the bot process to pick up and execute.
 *
 * See openspec design.md Decision 1.
 */
class CloseGiveawayMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Giveaway $giveaway) {}

    public function handle(): void
    {
        DiscordOutboundAction::query()->firstOrCreate([
            'giveaway_id' => $this->giveaway->id,
            'type' => DiscordOutboundAction::TYPE_CLOSE_GIVEAWAY_MESSAGE,
        ], [
            'payload' => [
                'discord_message_id' => $this->giveaway->discord_message_id,
                'channel_id' => $this->giveaway->channel_id,
            ],
            'status' => DiscordOutboundAction::STATUS_PENDING,
        ]);
    }
}
