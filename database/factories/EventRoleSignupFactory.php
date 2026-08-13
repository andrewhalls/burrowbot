<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DiscordMember;
use App\Models\EventOccurrence;
use App\Models\EventRole;
use App\Models\EventRoleSignup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRoleSignup>
 */
class EventRoleSignupFactory extends Factory
{
    protected $model = EventRoleSignup::class;

    public function definition(): array
    {
        return [
            'event_occurrence_id' => EventOccurrence::factory(),
            'discord_member_id' => DiscordMember::factory(),
            'event_role_id' => EventRole::factory(),
            'is_waitlisted' => false,
        ];
    }

    public function waitlisted(): static
    {
        return $this->state(['is_waitlisted' => true]);
    }
}
