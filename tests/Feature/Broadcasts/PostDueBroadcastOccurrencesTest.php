<?php

declare(strict_types=1);

use App\Models\Broadcast;
use App\Models\BroadcastOccurrence;
use App\Models\DiscordOutboundAction;
use App\Models\Guild;
use Illuminate\Support\Carbon;

it('enqueues a post_broadcast_message action for a due occurrence', function () {
    $guild = Guild::factory()->create(['name' => 'Demo Server']);
    $broadcast = Broadcast::factory()->for($guild)->create(['message_template' => 'Hello from {{guild_name}}!']);
    $occurrence = BroadcastOccurrence::factory()->fromBroadcast($broadcast)->create(['scheduled_post_at' => now()->subMinute()]);

    $this->artisan('broadcasts:post-due-occurrences')->assertSuccessful();

    $action = DiscordOutboundAction::query()->where('broadcast_occurrence_id', $occurrence->id)->first();
    expect($action)->not->toBeNull()
        ->and($action->type)->toBe(DiscordOutboundAction::TYPE_POST_BROADCAST_MESSAGE)
        ->and($action->payload['occurrence_id'])->toBe($occurrence->id)
        ->and($action->payload['channel_id'])->toBe($occurrence->channel_id)
        ->and($action->payload['message'])->toBe('Hello from Demo Server!');

    expect($occurrence->fresh()->status)->toBe(BroadcastOccurrence::STATUS_POSTED)
        ->and($occurrence->fresh()->posted_at)->not->toBeNull();
});

it('resolves date/time from the actual post moment, not the scheduled_post_at moment', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create([
        'message_template' => '{{date}} {{time}}',
        'recurrence_timezone' => 'UTC',
    ]);
    // Generated (scheduled) for well in the past relative to "posting" now.
    $occurrence = BroadcastOccurrence::factory()->fromBroadcast($broadcast)->create(['scheduled_post_at' => now()->subDays(3)]);

    $this->artisan('broadcasts:post-due-occurrences');

    $action = DiscordOutboundAction::query()->where('broadcast_occurrence_id', $occurrence->id)->first();
    $now = Carbon::now('UTC');
    expect($action->payload['message'])->toBe($now->format('M j, Y').' '.$now->format('g:ia'));
});

it('resolves next_occurrence_date for a recurring broadcast', function () {
    $guild = Guild::factory()->create();
    // The occurrence being posted is due (in the past); its series'
    // recurrence_start_at anchors the weekly rule one week earlier, so the
    // *next* occurrence after this one falls exactly one week after it -
    // i.e. right around "now".
    $startAt = now()->subWeek();
    $byDay = strtoupper(substr($startAt->format('D'), 0, 2));
    $broadcast = Broadcast::factory()->for($guild)->recurring("FREQ=WEEKLY;BYDAY={$byDay}", $startAt, 'UTC')->create([
        'message_template' => 'Next: {{next_occurrence_date}}',
    ]);
    $occurrence = BroadcastOccurrence::factory()->fromBroadcast($broadcast)->create([
        'scheduled_post_at' => $startAt->clone()->utc(),
    ]);

    $this->artisan('broadcasts:post-due-occurrences');

    $action = DiscordOutboundAction::query()->where('broadcast_occurrence_id', $occurrence->id)->first();
    $expectedNext = $startAt->clone()->addWeek();
    expect($action->payload['message'])->toBe('Next: '.$expectedNext->format('M j, Y'));
});

it('resolves next_occurrence_date to an empty string for a one-off broadcast', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create(['message_template' => 'Next: {{next_occurrence_date}}']);
    $occurrence = BroadcastOccurrence::factory()->fromBroadcast($broadcast)->create(['scheduled_post_at' => now()->subMinute()]);

    $this->artisan('broadcasts:post-due-occurrences');

    $action = DiscordOutboundAction::query()->where('broadcast_occurrence_id', $occurrence->id)->first();
    expect($action->payload['message'])->toBe('Next: ');
});

it('does not re-post an already-posted occurrence', function () {
    $occurrence = BroadcastOccurrence::factory()->posted()->create();

    $this->artisan('broadcasts:post-due-occurrences');

    expect(DiscordOutboundAction::query()->where('broadcast_occurrence_id', $occurrence->id)->count())->toBe(0);
});

it('does not post a scheduled occurrence whose post time has not arrived yet', function () {
    $occurrence = BroadcastOccurrence::factory()->create(['scheduled_post_at' => now()->addWeek()]);

    $this->artisan('broadcasts:post-due-occurrences');

    expect(DiscordOutboundAction::query()->where('broadcast_occurrence_id', $occurrence->id)->count())->toBe(0)
        ->and($occurrence->fresh()->status)->toBe(BroadcastOccurrence::STATUS_SCHEDULED);
});

it('is idempotent when run twice for the same due occurrence', function () {
    $occurrence = BroadcastOccurrence::factory()->create(['scheduled_post_at' => now()->subMinute()]);

    $this->artisan('broadcasts:post-due-occurrences');
    $this->artisan('broadcasts:post-due-occurrences');

    expect(DiscordOutboundAction::query()->where('broadcast_occurrence_id', $occurrence->id)->count())->toBe(1);
});
