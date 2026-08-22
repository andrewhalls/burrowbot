<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guild>
 */
class GuildFactory extends Factory
{
    protected $model = Guild::class;

    public function definition(): array
    {
        return [
            'discord_guild_id' => fake()->unique()->numerify('##################'),
            'name' => fake()->company().' Server',
            'default_channel_id' => fake()->numerify('##################'),
            'is_active' => true,
            'popup_giveaway_winner_messages_enabled' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withPopupGiveawayWinnerMessagesDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'popup_giveaway_winner_messages_enabled' => false,
        ]);
    }
}
