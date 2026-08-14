<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DiscordChannel;
use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordChannel>
 */
class DiscordChannelFactory extends Factory
{
    protected $model = DiscordChannel::class;

    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'discord_channel_id' => fake()->unique()->numerify('##################'),
            'name' => fake()->unique()->word(),
        ];
    }
}
