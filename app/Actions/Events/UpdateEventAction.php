<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\EventOccurrence;
use Illuminate\Support\Facades\Storage;

/**
 * Edits an event series. Existing `event_occurrences` rows are
 * snapshotted and are never mutated by this action - only occurrences
 * generated after this edit reflect the new values.
 *
 * See openspec specs/events - "Editing an event series only affects
 * future occurrences".
 */
class UpdateEventAction
{
    public function execute(Event $event, array $attributes): Event
    {
        $attributes = array_intersect_key(
            $attributes,
            array_flip([
                'title', 'description', 'image_path', 'channel_id', 'posting_mode', 'event_role_set_id',
                'recurrence_rule', 'recurrence_start_at', 'recurrence_timezone',
            ]),
        );

        // The old path is only safe to delete from disk once nothing else
        // still points at it - an already-generated occurrence snapshots
        // the same image_path string (not its own copy of the file), so
        // deleting unconditionally here would break "occurrences generated
        // before the change keep their original image" the moment a
        // series image is replaced. Mirrors UpdateStandardGiveawayAction
        // (design.md Decision 4).
        $oldPath = $event->image_path;
        if (
            array_key_exists('image_path', $attributes)
            && $oldPath !== null
            && $oldPath !== $attributes['image_path']
            && ! EventOccurrence::query()->where('image_path', $oldPath)->exists()
        ) {
            Storage::disk('public')->delete($oldPath);
        }

        $event->fill($attributes)->save();

        return $event;
    }
}
