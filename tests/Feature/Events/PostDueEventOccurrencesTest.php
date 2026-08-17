<?php

declare(strict_types=1);

use App\Models\DiscordOutboundAction;
use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\EventRoleSet;

it('enqueues a post_event_occurrence_message action for a due message-mode occurrence', function () {
    $roleSet = EventRoleSet::factory()->withRoles(2)->create();
    $occurrence = EventOccurrence::factory()->create([
        'posting_mode' => Event::POSTING_MODE_MESSAGE,
        'event_role_set_id' => $roleSet->id,
        'scheduled_start_at' => now()->subMinute(),
    ]);

    $this->artisan('events:post-due-occurrences')->assertSuccessful();

    $action = DiscordOutboundAction::query()->where('event_occurrence_id', $occurrence->id)->first();
    expect($action)->not->toBeNull()
        ->and($action->type)->toBe(DiscordOutboundAction::TYPE_POST_EVENT_OCCURRENCE_MESSAGE)
        ->and($action->payload['occurrence_id'])->toBe($occurrence->id)
        ->and($action->payload['roles'])->toHaveCount(2);

    expect($occurrence->fresh()->status)->toBe(EventOccurrence::STATUS_POSTED);
});

it('enqueues a post_event_occurrence_thread action for a due thread-mode occurrence', function () {
    $occurrence = EventOccurrence::factory()->create([
        'posting_mode' => Event::POSTING_MODE_THREAD,
        'scheduled_start_at' => now()->subMinute(),
    ]);

    $this->artisan('events:post-due-occurrences');

    $action = DiscordOutboundAction::query()->where('event_occurrence_id', $occurrence->id)->first();
    expect($action->type)->toBe(DiscordOutboundAction::TYPE_POST_EVENT_OCCURRENCE_THREAD);
});

it('includes the occurrence\'s image url in the outbound payload when set', function () {
    $occurrence = EventOccurrence::factory()->withImage('event-images/abc.jpg')->create(['scheduled_start_at' => now()->subMinute()]);

    $this->artisan('events:post-due-occurrences');

    $action = DiscordOutboundAction::query()->where('event_occurrence_id', $occurrence->id)->first();
    expect($action->payload['image_url'])->toContain('event-images/abc.jpg');
});

it('leaves the image url null in the outbound payload when unset', function () {
    $occurrence = EventOccurrence::factory()->create(['scheduled_start_at' => now()->subMinute()]);

    $this->artisan('events:post-due-occurrences');

    $action = DiscordOutboundAction::query()->where('event_occurrence_id', $occurrence->id)->first();
    expect($action->payload['image_url'])->toBeNull();
});

it('does not re-post an already-posted occurrence', function () {
    $occurrence = EventOccurrence::factory()->posted()->create();

    $this->artisan('events:post-due-occurrences');

    expect(DiscordOutboundAction::query()->where('event_occurrence_id', $occurrence->id)->count())->toBe(0);
});

it('does not post a scheduled occurrence whose start time has not arrived yet', function () {
    $occurrence = EventOccurrence::factory()->create(['scheduled_start_at' => now()->addWeek()]);

    $this->artisan('events:post-due-occurrences');

    expect(DiscordOutboundAction::query()->where('event_occurrence_id', $occurrence->id)->count())->toBe(0)
        ->and($occurrence->fresh()->status)->toBe(EventOccurrence::STATUS_SCHEDULED);
});
