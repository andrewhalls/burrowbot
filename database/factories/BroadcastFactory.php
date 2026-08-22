<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Broadcast;
use App\Models\Guild;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Broadcast>
 */
class BroadcastFactory extends Factory
{
    protected $model = Broadcast::class;

    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'title' => fake()->sentence(3),
            'message_template' => 'Reminder: {{guild_name}} events happen in {{channel}}.',
            'channel_id' => fake()->numerify('##################'),
            'status' => Broadcast::STATUS_ACTIVE,
            'recurrence_rule' => null,
            'recurrence_start_at' => null,
            'recurrence_timezone' => null,
        ];
    }

    public function createdBy(User $user): static
    {
        return $this->state(['created_by_user_id' => $user->id]);
    }

    public function recurring(string $rrule, ?\DateTimeInterface $startAt = null, string $timezone = 'UTC'): static
    {
        return $this->state([
            'recurrence_rule' => $rrule,
            'recurrence_start_at' => $startAt ?? now(),
            'recurrence_timezone' => $timezone,
        ]);
    }

    public function paused(): static
    {
        return $this->state(['status' => Broadcast::STATUS_PAUSED]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => Broadcast::STATUS_CANCELLED]);
    }

    public function archived(): static
    {
        return $this->state([
            'status' => Broadcast::STATUS_CANCELLED,
            'archived_at' => now()->subDay(),
        ]);
    }
}
