<?php

declare(strict_types=1);

use App\Actions\Events\CreateEventAction;
use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\User;

it('creates a one-off event with a single occurrence immediately', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(2)->create();
    $startAt = now()->addWeek();

    $event = (new CreateEventAction)->execute(
        $guild, $roleSet, 'Game Night', 'Come play games', '12345', Event::POSTING_MODE_MESSAGE,
        null, $startAt, 'UTC',
    );

    expect($event->isRecurring())->toBeFalse()
        ->and($event->occurrences)->toHaveCount(1);

    $occurrence = $event->occurrences->first();
    expect($occurrence->title)->toBe('Game Night')
        ->and($occurrence->event_role_set_id)->toBe($roleSet->id)
        ->and($occurrence->scheduled_start_at->timestamp)->toBe($startAt->timestamp)
        ->and($occurrence->status)->toBe(EventOccurrence::STATUS_SCHEDULED);
});

it('records the creator when provided', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(2)->create();
    $user = User::factory()->create();

    $event = (new CreateEventAction)->execute(
        $guild, $roleSet, 'Game Night', 'Come play games', '12345', Event::POSTING_MODE_MESSAGE,
        null, now()->addWeek(), 'UTC', createdBy: $user,
    );

    expect($event->created_by_user_id)->toBe($user->id);
});

it('records the image and snapshots it into the one-off occurrence', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(2)->create();

    $event = (new CreateEventAction)->execute(
        $guild, $roleSet, 'Game Night', 'Come play games', '12345', Event::POSTING_MODE_MESSAGE,
        null, now()->addWeek(), 'UTC', 'event-images/abc.jpg',
    );

    expect($event->image_path)->toBe('event-images/abc.jpg')
        ->and($event->occurrences->first()->image_path)->toBe('event-images/abc.jpg');
});

it('leaves the image null when not provided', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(2)->create();

    $event = (new CreateEventAction)->execute(
        $guild, $roleSet, 'Game Night', 'Come play games', '12345', Event::POSTING_MODE_MESSAGE,
        null, now()->addWeek(), 'UTC',
    );

    expect($event->image_path)->toBeNull();
});

it('leaves the creator null when not provided', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(2)->create();

    $event = (new CreateEventAction)->execute(
        $guild, $roleSet, 'Game Night', 'Come play games', '12345', Event::POSTING_MODE_MESSAGE,
        null, now()->addWeek(), 'UTC',
    );

    expect($event->created_by_user_id)->toBeNull();
});

it('creates a recurring event with no occurrences yet', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();

    $event = (new CreateEventAction)->execute(
        $guild, $roleSet, 'Raid Night', 'Weekly raid', '12345', Event::POSTING_MODE_THREAD,
        'FREQ=WEEKLY;BYDAY=WE', now()->addWeek(), 'UTC',
    );

    expect($event->isRecurring())->toBeTrue()
        ->and($event->occurrences)->toHaveCount(0);
});
