<?php

declare(strict_types=1);

namespace App\Actions\Broadcasts;

use App\Models\Broadcast;
use App\Models\BroadcastOccurrence;
use App\Models\Guild;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a broadcast series. A one-off broadcast (no recurrence rule)
 * also gets its single occurrence created immediately, snapshotted from
 * the broadcast. A recurring broadcast's occurrences are left to the
 * generation job (see Support\Events\ExpandRecurrenceRule and the
 * broadcasts:generate-occurrences command).
 *
 * See openspec specs/broadcasts - "Broadcast creation";
 * specs/broadcast-occurrences - "One-off broadcasts generate exactly one
 * occurrence".
 */
class CreateBroadcastAction
{
    public function execute(
        Guild $guild,
        string $title,
        string $messageTemplate,
        string $channelId,
        ?string $recurrenceRule,
        ?\DateTimeInterface $recurrenceStartAt,
        ?string $recurrenceTimezone,
        ?User $createdBy = null,
    ): Broadcast {
        return DB::transaction(function () use (
            $guild, $title, $messageTemplate, $channelId,
            $recurrenceRule, $recurrenceStartAt, $recurrenceTimezone, $createdBy,
        ) {
            $broadcast = $guild->broadcasts()->create([
                'created_by_user_id' => $createdBy?->id,
                'title' => $title,
                'message_template' => $messageTemplate,
                'channel_id' => $channelId,
                'status' => Broadcast::STATUS_ACTIVE,
                'recurrence_rule' => $recurrenceRule,
                'recurrence_start_at' => $recurrenceStartAt,
                'recurrence_timezone' => $recurrenceTimezone,
            ]);

            if (! $broadcast->isRecurring() && $recurrenceStartAt !== null) {
                $broadcast->occurrences()->create([
                    'message_template' => $broadcast->message_template,
                    'channel_id' => $broadcast->channel_id,
                    // Unlike Broadcast.recurrence_start_at (deliberately
                    // kept as wall-clock numbers, paired with
                    // recurrence_timezone, for ExpandRecurrenceRule), the
                    // occurrence's scheduled_post_at is compared directly
                    // against now() (PostDueBroadcastOccurrences) and so
                    // must be a true UTC instant - ->clone()->utc() here,
                    // not the shared $recurrenceStartAt itself. Mirrors
                    // CreateEventAction.
                    'scheduled_post_at' => $recurrenceStartAt->clone()->utc(),
                    'status' => BroadcastOccurrence::STATUS_SCHEDULED,
                ]);
            }

            return $broadcast;
        });
    }
}
