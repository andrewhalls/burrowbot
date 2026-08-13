<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRoleSet>
 */
class EventRoleSetFactory extends Factory
{
    protected $model = EventRoleSet::class;

    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'name' => fake()->unique()->words(2, true).' Roles',
            'allow_multiple_roles' => false,
        ];
    }

    public function allowMultipleRoles(): static
    {
        return $this->state(['allow_multiple_roles' => true]);
    }

    /**
     * Attach a given number of uncapped roles after creating the role set.
     */
    public function withRoles(int $count = 3): static
    {
        return $this->afterCreating(function (EventRoleSet $roleSet) use ($count): void {
            EventRole::factory()
                ->count($count)
                ->sequence(fn ($sequence) => ['sort_order' => $sequence->index])
                ->for($roleSet, 'eventRoleSet')
                ->create();
        });
    }
}
