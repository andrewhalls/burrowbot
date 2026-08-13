<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CollectionTheme;
use App\Models\Giveaway;
use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Giveaway>
 */
class GiveawayFactory extends Factory
{
    protected $model = Giveaway::class;

    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'collection_theme_id' => CollectionTheme::factory(),
            'channel_id' => fake()->numerify('##################'),
            'duration_minutes' => fake()->numberBetween(5, 120),
            'status' => Giveaway::STATUS_DRAFT,
            'discord_message_id' => null,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = now();

            return [
                'status' => Giveaway::STATUS_ACTIVE,
                'discord_message_id' => fake()->numerify('##################'),
                'starts_at' => $startedAt,
                'ends_at' => $startedAt->clone()->addMinutes($attributes['duration_minutes'] ?? 30),
            ];
        });
    }

    public function scheduledFor(Carbon $when): static
    {
        return $this->state(['scheduled_start_at' => $when]);
    }

    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            $endedAt = now()->subMinute();

            return [
                'status' => Giveaway::STATUS_CLOSED,
                'discord_message_id' => fake()->numerify('##################'),
                'starts_at' => $endedAt->clone()->subMinutes($attributes['duration_minutes'] ?? 30),
                'ends_at' => $endedAt,
            ];
        });
    }
}
