<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuildAdmin>
 */
class GuildAdminFactory extends Factory
{
    protected $model = GuildAdmin::class;

    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'user_id' => User::factory(),
            'role' => 'admin',
            'source' => GuildAdmin::SOURCE_DISCORD_SYNC,
            'sections' => null,
        ];
    }

    public function discordSynced(): static
    {
        return $this->state([
            'source' => GuildAdmin::SOURCE_DISCORD_SYNC,
            'sections' => null,
        ]);
    }

    /**
     * @param  list<string>  $sections
     */
    public function granted(array $sections): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => GuildAdmin::SOURCE_GRANTED,
            'sections' => $sections,
            'discord_user_id' => $attributes['discord_user_id'] ?? fake()->numerify('##################'),
        ]);
    }

    /**
     * A grant created for someone who hasn't logged into Burrow yet -
     * no `user_id`, resolved on their first login (design.md Decision 3).
     *
     * @param  list<string>  $sections
     */
    public function pending(string $discordUserId, array $sections): static
    {
        return $this->state([
            'user_id' => null,
            'discord_user_id' => $discordUserId,
            'source' => GuildAdmin::SOURCE_GRANTED,
            'sections' => $sections,
        ]);
    }
}
