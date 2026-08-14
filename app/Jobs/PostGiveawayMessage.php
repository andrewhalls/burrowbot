<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DiscordOutboundAction;
use App\Models\Giveaway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Enqueues a "post this giveaway to Discord" outbound action for the bot
 * process to pick up and execute - this job itself never calls Discord.
 *
 * See openspec design.md Decision 1.
 */
class PostGiveawayMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Giveaway $giveaway) {}

    public function handle(): void
    {
        DiscordOutboundAction::query()->firstOrCreate([
            'giveaway_id' => $this->giveaway->id,
            'type' => DiscordOutboundAction::TYPE_POST_GIVEAWAY_MESSAGE,
        ], [
            'payload' => [
                'channel_id' => $this->giveaway->channel_id,
                'collection_theme_name' => $this->giveaway->collectionTheme->name,
                'ends_at' => $this->giveaway->ends_at?->toIso8601String(),
                'description' => $this->giveaway->description,
                'image_url' => $this->giveaway->image_url,
            ],
            'status' => DiscordOutboundAction::STATUS_PENDING,
        ]);
    }
}
