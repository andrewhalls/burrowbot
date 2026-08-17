<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'event_role_set_id' => EventRoleSet::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'channel_id' => fake()->numerify('##################'),
            'posting_mode' => Event::POSTING_MODE_MESSAGE,
            'status' => Event::STATUS_ACTIVE,
            'recurrence_rule' => null,
            'recurrence_start_at' => null,
            'recurrence_timezone' => null,
        ];
    }

    public function threadMode(): static
    {
        return $this->state(['posting_mode' => Event::POSTING_MODE_THREAD]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(['created_by_user_id' => $user->id]);
    }

    public function withImage(string $path): static
    {
        return $this->state(['image_path' => $path]);
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
        return $this->state(['status' => Event::STATUS_PAUSED]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => Event::STATUS_CANCELLED]);
    }

    public function archived(): static
    {
        return $this->state([
            'status' => Event::STATUS_CANCELLED,
            'archived_at' => now()->subDay(),
        ]);
    }
}
