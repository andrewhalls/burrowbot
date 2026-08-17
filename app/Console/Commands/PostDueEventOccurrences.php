<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DiscordOutboundAction;
use App\Models\EventOccurrence;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Finds `scheduled` occurrences whose scheduled start time has arrived
 * and enqueues the outbound action that posts them to Discord. This
 * command is where a future per-event lead time (post N minutes/hours
 * before the start time, rather than at it) would be checked.
 *
 * See openspec specs/event-occurrences - "Posting an occurrence to Discord".
 */
#[Signature('events:post-due-occurrences')]
#[Description('Enqueue the outbound action to post each scheduled occurrence that is due.')]
class PostDueEventOccurrences extends Command
{
    public function handle(): int
    {
        $occurrences = EventOccurrence::query()
            ->where('status', EventOccurrence::STATUS_SCHEDULED)
            ->where('scheduled_start_at', '<=', now())
            ->with('eventRoleSet.roles')
            ->get();

        foreach ($occurrences as $occurrence) {
            DiscordOutboundAction::query()->firstOrCreate([
                'event_occurrence_id' => $occurrence->id,
                'type' => $occurrence->isThreadMode()
                    ? DiscordOutboundAction::TYPE_POST_EVENT_OCCURRENCE_THREAD
                    : DiscordOutboundAction::TYPE_POST_EVENT_OCCURRENCE_MESSAGE,
            ], [
                'payload' => [
                    'occurrence_id' => $occurrence->id,
                    'channel_id' => $occurrence->channel_id,
                    'title' => $occurrence->title,
                    'description' => $occurrence->description,
                    'image_url' => $occurrence->image_url,
                    'scheduled_start_at' => $occurrence->scheduled_start_at->toIso8601String(),
                    'roles' => $occurrence->eventRoleSet->roles->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                    ])->all(),
                ],
                'status' => DiscordOutboundAction::STATUS_PENDING,
            ]);

            $occurrence->update(['status' => EventOccurrence::STATUS_POSTED]);
        }

        $this->info("Posted {$occurrences->count()} occurrence(s).");

        return self::SUCCESS;
    }
}
