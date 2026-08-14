<?php

declare(strict_types=1);

use App\Actions\Events\CreateEventAction;
use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\User;
use Illuminate\Support\Carbon;

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

it('stores the one-off occurrence\'s scheduled_start_at as a true UTC instant, not reinterpreted wall-clock', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();

    // A wall-clock-in-New-York Carbon (mirrors how CreateEvent::save()
    // parses the browser's local date/time before ->utc() is deliberately
    // withheld for the Event.recurrence_start_at field).
    $localStartAt = Carbon::parse(now()->addWeek()->toDateString().' 20:00', 'America/New_York');

    $event = (new CreateEventAction)->execute(
        $guild, $roleSet, 'Game Night', 'Come play games', '12345', Event::POSTING_MODE_MESSAGE,
        null, $localStartAt, 'America/New_York',
    );

    $occurrence = $event->occurrences->first();

    // The stored instant must match the real moment 8pm Eastern represents
    // (not the naive "20:00 read back as UTC" bug), and must therefore be
    // in the future - hasStarted() must be false.
    expect($occurrence->scheduled_start_at->timestamp)->toBe($localStartAt->clone()->utc()->timestamp)
        ->and($occurrence->hasStarted())->toBeFalse();
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
