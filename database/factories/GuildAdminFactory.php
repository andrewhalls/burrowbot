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
        ];
    }
}
