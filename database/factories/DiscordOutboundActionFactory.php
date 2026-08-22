<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BroadcastOccurrence;
use App\Models\DiscordOutboundAction;
use App\Models\EventOccurrence;
use App\Models\Giveaway;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordOutboundAction>
 */
class DiscordOutboundActionFactory extends Factory
{
    protected $model = DiscordOutboundAction::class;

    public function definition(): array
    {
        return [
            'type' => DiscordOutboundAction::TYPE_POST_GIVEAWAY_MESSAGE,
            'giveaway_id' => Giveaway::factory(),
            'event_occurrence_id' => null,
            'standard_giveaway_occurrence_id' => null,
            'broadcast_occurrence_id' => null,
            'payload' => ['channel_id' => fake()->numerify('##################')],
            'status' => DiscordOutboundAction::STATUS_PENDING,
            'attempts' => 0,
            'last_failure_reason' => null,
        ];
    }

    public function forEventOccurrence(?EventOccurrence $occurrence = null): static
    {
        return $this->state(fn () => [
            'type' => DiscordOutboundAction::TYPE_POST_EVENT_OCCURRENCE_MESSAGE,
            'giveaway_id' => null,
            'event_occurrence_id' => $occurrence?->id ?? EventOccurrence::factory(),
        ]);
    }

    public function forStandardGiveawayOccurrence(?StandardGiveawayOccurrence $occurrence = null): static
    {
        return $this->state(fn () => [
            'type' => DiscordOutboundAction::TYPE_POST_STANDARD_GIVEAWAY_MESSAGE,
            'giveaway_id' => null,
            'standard_giveaway_occurrence_id' => $occurrence?->id ?? StandardGiveawayOccurrence::factory(),
        ]);
    }

    public function forBroadcastOccurrence(?BroadcastOccurrence $occurrence = null): static
    {
        return $this->state(fn () => [
            'type' => DiscordOutboundAction::TYPE_POST_BROADCAST_MESSAGE,
            'giveaway_id' => null,
            'broadcast_occurrence_id' => $occurrence?->id ?? BroadcastOccurrence::factory(),
        ]);
    }
}
