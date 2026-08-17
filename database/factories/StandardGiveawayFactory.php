<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StandardGiveaway>
 */
class StandardGiveawayFactory extends Factory
{
    protected $model = StandardGiveaway::class;

    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'channel_id' => fake()->numerify('##################'),
            'posting_mode' => StandardGiveaway::POSTING_MODE_MESSAGE,
            'status' => StandardGiveaway::STATUS_ACTIVE,
            'winner_count' => 1,
            'requires_booster' => false,
            'duration_minutes' => fake()->numberBetween(60, 10080), // 1 hour to 1 week
            'recurrence_rule' => null,
            'recurrence_start_at' => null,
            'recurrence_timezone' => null,
        ];
    }

    public function threadMode(): static
    {
        return $this->state(['posting_mode' => StandardGiveaway::POSTING_MODE_THREAD]);
    }

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

    public function createdBy(User $user): static
    {
        return $this->state(['created_by_user_id' => $user->id]);
    }

    public function boosterOnly(): static
    {
        return $this->state(['requires_booster' => true]);
    }

    public function winnerCount(int $count): static
    {
        return $this->state(['winner_count' => $count]);
    }

    public function recurring(string $rrule, ?\DateTimeInterface $startAt = null, string $timezone = 'UTC'): static
    {
        return $this->state([
            'recurrence_rule' => $rrule,
            'recurrence_start_at' => $startAt ?? now(),
            'recurrence_timezone' => $timezone,
        ]);
    }

    public function paused(): static
    {
        return $this->state(['status' => StandardGiveaway::STATUS_PAUSED]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => StandardGiveaway::STATUS_CANCELLED]);
    }

    public function archived(): static
    {
        return $this->state([
            'status' => StandardGiveaway::STATUS_CANCELLED,
            'archived_at' => now()->subDay(),
        ]);
    }
}
