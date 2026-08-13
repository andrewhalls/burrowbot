<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DiscordMember;
use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordMember>
 */
class DiscordMemberFactory extends Factory
{
    protected $model = DiscordMember::class;

    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'discord_user_id' => fake()->unique()->numerify('##################'),
            'username' => fake()->unique()->userName(),
            'avatar_url' => fake()->imageUrl(),
        ];
    }
}
