<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Models\CollectionThemeItem;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates a standard giveaway series. A one-off giveaway (no recurrence
 * rule) also gets its single occurrence created immediately, snapshotted
 * from the giveaway - mirrors CreateEventAction.
 *
 * See openspec specs/standard-giveaways - "Standard giveaway creation",
 * "Prize items selected from existing collection theme items";
 * specs/standard-giveaway-occurrences - "One-off standard giveaways".
 */
class CreateStandardGiveawayAction
{
    /**
     * @param  list<int>  $prizeCollectionThemeItemIds
     * @param  list<string>  $requiredDiscordRoleIds
     */
    public function execute(
        Guild $guild,
        string $title,
        string $description,
        string $channelId,
        string $postingMode,
        int $winnerCount,
        bool $requiresBooster,
        int $durationMinutes,
        array $prizeCollectionThemeItemIds,
        array $requiredDiscordRoleIds,
        ?string $recurrenceRule,
        ?\DateTimeInterface $scheduledPostAt,
        ?string $recurrenceTimezone,
        ?string $imagePath = null,
        ?User $createdBy = null,
        ?string $bannerImagePath = null,
        ?string $claimLink = null,
        ?int $claimDeadlineHours = null,
        ?string $congratsMessageTemplate = null,
    ): StandardGiveaway {
        $prizeCollectionThemeItemIds = array_values(array_unique($prizeCollectionThemeItemIds));

        if ($prizeCollectionThemeItemIds === []) {
            throw new InvalidArgumentException('A standard giveaway must have at least one prize item.');
        }

        $validItemCount = CollectionThemeItem::query()
            ->whereIn('id', $prizeCollectionThemeItemIds)
            ->whereHas('collectionTheme', fn ($query) => $query->where('guild_id', $guild->id))
            ->count();

        if ($validItemCount !== count($prizeCollectionThemeItemIds)) {
            throw new InvalidArgumentException('One or more prize items do not belong to this guild.');
        }

        return DB::transaction(function () use (
            $guild, $title, $description, $channelId, $postingMode, $winnerCount,
            $requiresBooster, $durationMinutes, $prizeCollectionThemeItemIds,
            $requiredDiscordRoleIds, $recurrenceRule, $scheduledPostAt, $recurrenceTimezone, $imagePath, $createdBy,
            $bannerImagePath, $claimLink, $claimDeadlineHours, $congratsMessageTemplate,
        ) {
            $giveaway = $guild->standardGiveaways()->create([
                'created_by_user_id' => $createdBy?->id,
                'title' => $title,
                'description' => $description,
                'image_path' => $imagePath,
                'banner_image_path' => $bannerImagePath,
                'channel_id' => $channelId,
                'posting_mode' => $postingMode,
                'status' => StandardGiveaway::STATUS_ACTIVE,
                'winner_count' => $winnerCount,
                'requires_booster' => $requiresBooster,
                'duration_minutes' => $durationMinutes,
                'claim_link' => $claimLink,
                'claim_deadline_hours' => $claimDeadlineHours,
                'congrats_message_template' => $congratsMessageTemplate,
                'recurrence_rule' => $recurrenceRule,
                'recurrence_start_at' => $scheduledPostAt,
                'recurrence_timezone' => $recurrenceTimezone,
            ]);

            foreach ($prizeCollectionThemeItemIds as $itemId) {
                $giveaway->prizeItems()->create(['collection_theme_item_id' => $itemId]);
            }

            foreach (array_unique($requiredDiscordRoleIds) as $roleId) {
                $giveaway->requiredRoles()->create(['discord_role_id' => $roleId]);
            }

            if (! $giveaway->isRecurring() && $scheduledPostAt !== null) {
                $giveaway->occurrences()->create([
                    'title' => $giveaway->title,
                    'description' => $giveaway->description,
                    'image_path' => $giveaway->image_path,
                    'banner_image_path' => $giveaway->banner_image_path,
                    'channel_id' => $giveaway->channel_id,
                    'posting_mode' => $giveaway->posting_mode,
                    'requires_booster' => $giveaway->requires_booster,
                    'winner_count' => $giveaway->winner_count,
                    'duration_minutes' => $giveaway->duration_minutes,
                    'prize_item_ids' => $prizeCollectionThemeItemIds,
                    'required_role_ids' => array_values(array_unique($requiredDiscordRoleIds)),
                    'claim_link' => $giveaway->claim_link,
                    'claim_deadline_hours' => $giveaway->claim_deadline_hours,
                    'congrats_message_template' => $giveaway->congrats_message_template,
                    // Unlike StandardGiveaway.recurrence_start_at
                    // (deliberately kept as wall-clock numbers, paired with
                    // recurrence_timezone, for ExpandRecurrenceRule), the
                    // occurrence's scheduled_post_at is compared directly
                    // against now() (PostDueStandardGiveawayOccurrences)
                    // and so must be a true UTC instant - ->clone()->utc()
                    // here, not the shared $scheduledPostAt itself. Mirrors
                    // CreateEventAction's one-off path.
                    'scheduled_post_at' => $scheduledPostAt->clone()->utc(),
                    'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
                ]);
            }

            return $giveaway->load('prizeItems', 'requiredRoles');
        });
    }
}
