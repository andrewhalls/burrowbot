<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CollectionThemeItem;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StandardGiveawayOccurrence>
 */
class StandardGiveawayOccurrenceFactory extends Factory
{
    protected $model = StandardGiveawayOccurrence::class;

    public function definition(): array
    {
        return [
            'standard_giveaway_id' => StandardGiveaway::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'channel_id' => fake()->numerify('##################'),
            'posting_mode' => StandardGiveaway::POSTING_MODE_MESSAGE,
            'requires_booster' => false,
            'winner_count' => 1,
            'duration_minutes' => 60,
            'prize_item_ids' => [CollectionThemeItem::factory()->create()->id],
            'required_role_ids' => [],
            'scheduled_post_at' => now()->addHour(),
            'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
            'posted_at' => null,
            'ends_at' => null,
            'discord_thread_id' => null,
            'discord_message_id' => null,
        ];
    }

    /**
     * Snapshot scalar/list fields from a real StandardGiveaway, matching
     * what generation actually does.
     */
    public function withImage(string $path): static
    {
        return $this->state(['image_path' => $path]);
    }

    public function withBannerImage(string $path): static
    {
        return $this->state(['banner_image_path' => $path]);
    }

    public function withClaimDetails(string $claimLink, int $claimDeadlineHours, string $congratsMessageTemplate): static
    {
        return $this->state([
            'claim_link' => $claimLink,
            'claim_deadline_hours' => $claimDeadlineHours,
            'congrats_message_template' => $congratsMessageTemplate,
        ]);
    }

    public function fromGiveaway(StandardGiveaway $giveaway): static
    {
        return $this->state([
            'standard_giveaway_id' => $giveaway->id,
            'title' => $giveaway->title,
            'description' => $giveaway->description,
            'image_path' => $giveaway->image_path,
            'banner_image_path' => $giveaway->banner_image_path,
            'channel_id' => $giveaway->channel_id,
            'posting_mode' => $giveaway->posting_mode,
            'requires_booster' => $giveaway->requires_booster,
            'winner_count' => $giveaway->winner_count,
            'duration_minutes' => $giveaway->duration_minutes,
            'prize_item_ids' => $giveaway->prizeItems()->pluck('collection_theme_item_id')->all(),
            'required_role_ids' => $giveaway->requiredRoles()->pluck('discord_role_id')->all(),
            'claim_link' => $giveaway->claim_link,
            'claim_deadline_hours' => $giveaway->claim_deadline_hours,
            'congrats_message_template' => $giveaway->congrats_message_template,
        ]);
    }

    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StandardGiveawayOccurrence::STATUS_POSTED,
            'posted_at' => now(),
            'ends_at' => now()->addMinutes($attributes['duration_minutes'] ?? 60),
            'discord_message_id' => ($attributes['posting_mode'] ?? StandardGiveaway::POSTING_MODE_MESSAGE) === StandardGiveaway::POSTING_MODE_THREAD
                ? null
                : fake()->numerify('##################'),
            'discord_thread_id' => ($attributes['posting_mode'] ?? StandardGiveaway::POSTING_MODE_MESSAGE) === StandardGiveaway::POSTING_MODE_THREAD
                ? fake()->numerify('##################')
                : null,
        ]);
    }

    public function ended(): static
    {
        return $this->state([
            'status' => StandardGiveawayOccurrence::STATUS_POSTED,
            'posted_at' => now()->subDay(),
            'ends_at' => now()->subMinute(),
        ]);
    }

    public function closed(): static
    {
        return $this->state([
            'status' => StandardGiveawayOccurrence::STATUS_CLOSED,
            'posted_at' => now()->subDay(),
            'ends_at' => now()->subMinute(),
        ]);
    }
}
