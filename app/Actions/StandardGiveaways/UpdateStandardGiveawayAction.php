<?php

declare(strict_types=1);

namespace App\Actions\StandardGiveaways;

use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Support\Facades\Storage;

/**
 * Edits a standard giveaway series. Existing `standard_giveaway_occurrences`
 * rows are snapshotted and never mutated by this action - only occurrences
 * generated after this edit reflect the new values.
 *
 * See openspec specs/standard-giveaways - "Editing a standard giveaway
 * series only affects future occurrences".
 */
class UpdateStandardGiveawayAction
{
    /**
     * @param  array<string, mixed>  $attributes  May include 'prize_item_ids'
     *   (list<int> of collection theme item ids) and/or 'required_role_ids'
     *   (list<string> of Discord role ids) - when present, the giveaway's
     *   prize items/required roles are replaced wholesale with the given
     *   set (design.md Decision 3), matching the full-list-sync pattern
     *   already used for Discord channel/role sync. Omitting either key
     *   entirely leaves that set untouched.
     */
    public function execute(StandardGiveaway $giveaway, array $attributes): StandardGiveaway
    {
        $scalarAttributes = array_intersect_key(
            $attributes,
            array_flip([
                'title', 'description', 'channel_id', 'posting_mode', 'winner_count',
                'requires_booster', 'duration_minutes', 'image_path', 'banner_image_path',
                'claim_link', 'claim_deadline_hours', 'congrats_message_template',
                'per_winner_message_channel_id', 'per_winner_message_template',
                'recurrence_rule', 'recurrence_start_at', 'recurrence_timezone',
            ]),
        );

        // The old path is only safe to delete from disk once nothing else
        // still points at it - an already-generated occurrence snapshots
        // the same image_path string (not its own copy of the file), so
        // deleting unconditionally here would break "occurrences generated
        // before the change keep their original image" the moment a series
        // image is replaced (design.md Decision 2, revised during
        // implementation). Applies identically to banner_image_path.
        foreach (['image_path', 'banner_image_path'] as $imageField) {
            $oldPath = $giveaway->{$imageField};
            if (
                array_key_exists($imageField, $scalarAttributes)
                && $oldPath !== null
                && $oldPath !== $scalarAttributes[$imageField]
                && ! StandardGiveawayOccurrence::query()->where($imageField, $oldPath)->exists()
            ) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $giveaway->fill($scalarAttributes)->save();

        if (array_key_exists('prize_item_ids', $attributes)) {
            $giveaway->prizeItems()->delete();

            foreach (array_values(array_unique($attributes['prize_item_ids'])) as $itemId) {
                $giveaway->prizeItems()->create(['collection_theme_item_id' => $itemId]);
            }
        }

        if (array_key_exists('required_role_ids', $attributes)) {
            $giveaway->requiredRoles()->delete();

            foreach (array_values(array_unique($attributes['required_role_ids'])) as $roleId) {
                $giveaway->requiredRoles()->create(['discord_role_id' => $roleId]);
            }
        }

        return $giveaway->load('prizeItems', 'requiredRoles');
    }
}
