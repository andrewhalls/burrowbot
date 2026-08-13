<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventRole;
use App\Models\EventRoleSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRole>
 */
class EventRoleFactory extends Factory
{
    protected $model = EventRole::class;

    public function definition(): array
    {
        return [
            'event_role_set_id' => EventRoleSet::factory(),
            'name' => ucfirst(fake()->unique()->word()),
            'sort_order' => 0,
            'capacity_mode' => EventRole::CAPACITY_UNCAPPED,
            'capacity' => null,
        ];
    }

    public function capped(int $capacity): static
    {
        return $this->state([
            'capacity_mode' => EventRole::CAPACITY_CAPPED,
            'capacity' => $capacity,
        ]);
    }

    public function waitlisted(int $capacity): static
    {
        return $this->state([
            'capacity_mode' => EventRole::CAPACITY_WAITLISTED,
            'capacity' => $capacity,
        ]);
    }
}
