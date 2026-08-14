<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DiscordRole;
use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordRole>
 */
class DiscordRoleFactory extends Factory
{
    protected $model = DiscordRole::class;

    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'discord_role_id' => fake()->unique()->numerify('##################'),
            'name' => fake()->unique()->word(),
        ];
    }
}
