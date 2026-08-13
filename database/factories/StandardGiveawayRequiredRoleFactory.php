<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayRequiredRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StandardGiveawayRequiredRole>
 */
class StandardGiveawayRequiredRoleFactory extends Factory
{
    protected $model = StandardGiveawayRequiredRole::class;

    public function definition(): array
    {
        return [
            'standard_giveaway_id' => StandardGiveaway::factory(),
            'discord_role_id' => fake()->unique()->numerify('##################'),
        ];
    }
}
