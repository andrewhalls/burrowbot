<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BroadcastOccurrence;
use App\Models\DiscordOutboundAction;
use App\Support\Broadcasts\RenderBroadcastMessage;
use App\Support\Events\ExpandRecurrenceRule;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Finds `scheduled` occurrences whose scheduled post time has arrived,
 * resolves the message template's placeholders (design.md Decision 1 -
 * always as of the actual post moment, never generation time), and
 * enqueues the outbound action that posts them to Discord.
 *
 * See openspec specs/broadcast-occurrences - "Posting an occurrence to
 * Discord", "Message template placeholders".
 */
#[Signature('broadcasts:post-due-occurrences')]
#[Description('Enqueue the outbound action to post each scheduled broadcast occurrence that is due.')]
class PostDueBroadcastOccurrences extends Command
{
    private const LOOKAHEAD_DAYS = 90;

    public function handle(RenderBroadcastMessage $renderMessage, ExpandRecurrenceRule $expandRecurrenceRule): int
    {
        $occurrences = BroadcastOccurrence::query()
            ->where('status', BroadcastOccurrence::STATUS_SCHEDULED)
            ->where('scheduled_post_at', '<=', now())
            ->with('broadcast.guild')
            ->get();

        foreach ($occurrences as $occurrence) {
            DB::transaction(function () use ($occurrence, $renderMessage, $expandRecurrenceRule) {
                $postedAt = now();
                $broadcast = $occurrence->broadcast;
                $timezone = $broadcast->recurrence_timezone ?? 'UTC';

                $nextOccurrenceDate = $broadcast->isRecurring()
                    ? collect($expandRecurrenceRule(
                        $broadcast->recurrence_rule,
                        $broadcast->recurrence_start_at,
                        $timezone,
                        $occurrence->scheduled_post_at->clone()->addSecond(),
                        $occurrence->scheduled_post_at->clone()->addDays(self::LOOKAHEAD_DAYS),
                    ))->first()
                    : null;

                $message = $renderMessage(
                    $occurrence->message_template,
                    $broadcast->guild->name,
                    $occurrence->channel_id,
                    $postedAt,
                    $timezone,
                    $nextOccurrenceDate,
                );

                DiscordOutboundAction::query()->firstOrCreate([
                    'broadcast_occurrence_id' => $occurrence->id,
                    'type' => DiscordOutboundAction::TYPE_POST_BROADCAST_MESSAGE,
                ], [
                    'payload' => [
                        'occurrence_id' => $occurrence->id,
                        'channel_id' => $occurrence->channel_id,
                        'message' => $message,
                    ],
                    'status' => DiscordOutboundAction::STATUS_PENDING,
                ]);

                $occurrence->update([
                    'status' => BroadcastOccurrence::STATUS_POSTED,
                    'posted_at' => $postedAt,
                ]);
            });
        }

        $this->info("Posted {$occurrences->count()} occurrence(s).");

        return self::SUCCESS;
    }
}
