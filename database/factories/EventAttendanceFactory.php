<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DiscordMember;
use App\Models\EventAttendance;
use App\Models\EventOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventAttendance>
 */
class EventAttendanceFactory extends Factory
{
    protected $model = EventAttendance::class;

    public function definition(): array
    {
        return [
            'event_occurrence_id' => EventOccurrence::factory(),
            'discord_member_id' => DiscordMember::factory(),
            'status' => EventAttendance::STATUS_ATTENDING,
        ];
    }

    public function notAttending(): static
    {
        return $this->state(['status' => EventAttendance::STATUS_NOT_ATTENDING]);
    }
}
