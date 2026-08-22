<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Broadcast;
use App\Models\BroadcastOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BroadcastOccurrence>
 */
class BroadcastOccurrenceFactory extends Factory
{
    protected $model = BroadcastOccurrence::class;

    public function definition(): array
    {
        return [
            'broadcast_id' => Broadcast::factory(),
            'message_template' => 'Reminder: {{guild_name}} events happen in {{channel}}.',
            'channel_id' => fake()->numerify('##################'),
            'scheduled_post_at' => now()->addDays(3),
            'status' => BroadcastOccurrence::STATUS_SCHEDULED,
            'posted_at' => null,
            'discord_message_id' => null,
        ];
    }

    /**
     * Snapshot this occurrence's message_template/channel_id from a real
     * Broadcast, matching what generation actually does.
     */
    public function fromBroadcast(Broadcast $broadcast): static
    {
        return $this->state([
            'broadcast_id' => $broadcast->id,
            'message_template' => $broadcast->message_template,
            'channel_id' => $broadcast->channel_id,
        ]);
    }

    public function posted(): static
    {
        return $this->state(fn () => [
            'status' => BroadcastOccurrence::STATUS_POSTED,
            'posted_at' => now(),
            'discord_message_id' => fake()->numerify('##################'),
        ]);
    }
}
