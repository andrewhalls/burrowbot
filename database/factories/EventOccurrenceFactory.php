<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\EventRoleSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventOccurrence>
 */
class EventOccurrenceFactory extends Factory
{
    protected $model = EventOccurrence::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'channel_id' => fake()->numerify('##################'),
            'posting_mode' => Event::POSTING_MODE_MESSAGE,
            'event_role_set_id' => EventRoleSet::factory(),
            'scheduled_start_at' => now()->addDays(3),
            'status' => EventOccurrence::STATUS_SCHEDULED,
            'discord_thread_id' => null,
            'discord_message_id' => null,
        ];
    }

    /**
     * Snapshot this occurrence's title/description/channel/posting_mode/role
     * set from a real Event, matching what generation actually does.
     */
    public function fromEvent(Event $event): static
    {
        return $this->state([
            'event_id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'image_path' => $event->image_path,
            'channel_id' => $event->channel_id,
            'posting_mode' => $event->posting_mode,
            'event_role_set_id' => $event->event_role_set_id,
        ]);
    }

    public function withImage(string $path): static
    {
        return $this->state(['image_path' => $path]);
    }

    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventOccurrence::STATUS_POSTED,
            'discord_message_id' => ($attributes['posting_mode'] ?? Event::POSTING_MODE_MESSAGE) === Event::POSTING_MODE_THREAD
                ? null
                : fake()->numerify('##################'),
            'discord_thread_id' => ($attributes['posting_mode'] ?? Event::POSTING_MODE_MESSAGE) === Event::POSTING_MODE_THREAD
                ? fake()->numerify('##################')
                : null,
        ]);
    }

    public function started(): static
    {
        return $this->state(['scheduled_start_at' => now()->subHour()]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => EventOccurrence::STATUS_COMPLETED,
            'scheduled_start_at' => now()->subDay(),
        ]);
    }
}
