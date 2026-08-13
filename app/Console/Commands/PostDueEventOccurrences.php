<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DiscordOutboundAction;
use App\Models\EventOccurrence;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Finds `scheduled` occurrences and enqueues the outbound action that
 * posts them to Discord. v1 posts every scheduled occurrence immediately
 * (see design.md Decision 2 and its Open Question on post lead time) -
 * this command is where a future per-event lead time would be checked.
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
