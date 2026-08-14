<?php

declare(strict_types=1);

namespace App\Actions\Giveaways;

use App\Models\CollectionTheme;
use App\Models\Giveaway;
use App\Models\Guild;

/**
 * Creates a giveaway in `draft` status. See openspec specs/giveaway-lifecycle
 * - "Giveaway creation".
 */
class CreateGiveawayAction
{
    public function execute(
        Guild $guild,
        CollectionTheme $theme,
        string $channelId,
        int $durationMinutes,
        ?\DateTimeInterface $scheduledStartAt = null,
        ?string $description = null,
        ?string $imagePath = null,
    ): Giveaway {
        return $guild->giveaways()->create([
            'collection_theme_id' => $theme->id,
            'channel_id' => $channelId,
            'duration_minutes' => $durationMinutes,
            'scheduled_start_at' => $scheduledStartAt,
            'description' => $description,
            'image_path' => $imagePath,
            'status' => Giveaway::STATUS_DRAFT,
        ]);
    }
}
