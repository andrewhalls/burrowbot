<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\EventRoleSet;

it('generates occurrences for an active weekly recurring event', function () {
    $roleSet = EventRoleSet::factory()->withRoles(1)->create();
    $event = Event::factory()->for($roleSet, 'eventRoleSet')->recurring(
        'FREQ=WEEKLY;BYDAY=WE',
        now()->next('Wednesday')->setTime(20, 0),
        'UTC',
    )->create();

    $this->artisan('events:generate-occurrences')->assertSuccessful();

    expect($event->occurrences()->count())->toBeGreaterThan(0);

    $first = $event->occurrences()->orderBy('scheduled_start_at')->first();
    expect($first->title)->toBe($event->title)
        ->and($first->event_role_set_id)->toBe($roleSet->id)
        ->and($first->status)->toBe(EventOccurrence::STATUS_SCHEDULED);
});

it('snapshots the event\'s current image into generated occurrences', function () {
    $event = Event::factory()->withImage('event-images/abc.jpg')->recurring(
        'FREQ=WEEKLY;BYDAY=WE',
        now()->next('Wednesday')->setTime(20, 0),
        'UTC',
    )->create();

    $this->artisan('events:generate-occurrences')->assertSuccessful();

    $first = $event->occurrences()->orderBy('scheduled_start_at')->first();
    expect($first->image_path)->toBe('event-images/abc.jpg');
});

it('stores scheduled_start_at as a true UTC instant, not the recurrence timezone\'s wall-clock digits', function () {
    $localStart = now('Asia/Tokyo')->next('Monday')->setTime(18, 0);
    $event = Event::factory()->recurring('FREQ=WEEKLY;BYDAY=MO', $localStart, 'Asia/Tokyo')->create();

    $this->artisan('events:generate-occurrences')->assertSuccessful();

    $first = $event->occurrences()->orderBy('scheduled_start_at')->first();

    // 18:00 in Asia/Tokyo (UTC+9, no DST) is 09:00 UTC - if scheduled_start_at
    // were wrongly stored as the wall-clock "18:00" digits instead of the
    // converted UTC instant, this would read "18:00" instead.
    expect($first->scheduled_start_at->clone()->utc()->format('H:i'))->toBe('09:00');
});

it('does not generate duplicate occurrences on a second run', function () {
    $event = Event::factory()->recurring(
        'FREQ=WEEKLY;BYDAY=WE',
        now()->next('Wednesday')->setTime(20, 0),
        'UTC',
    )->create();

    $this->artisan('events:generate-occurrences');
    $countAfterFirstRun = $event->occurrences()->count();

    $this->artisan('events:generate-occurrences');
    $countAfterSecondRun = $event->occurrences()->count();

    expect($countAfterSecondRun)->toBe($countAfterFirstRun);
});

it('does not generate occurrences for a one-off event', function () {
    $event = Event::factory()->create(); // recurrence_rule null

    $this->artisan('events:generate-occurrences');

    expect($event->occurrences()->count())->toBe(0);
});

it('does not generate occurrences for a paused or cancelled event', function () {
    $paused = Event::factory()->paused()->recurring('FREQ=DAILY', now()->addDay(), 'UTC')->create();
    $cancelled = Event::factory()->cancelled()->recurring('FREQ=DAILY', now()->addDay(), 'UTC')->create();

    $this->artisan('events:generate-occurrences');

    expect($paused->occurrences()->count())->toBe(0)
        ->and($cancelled->occurrences()->count())->toBe(0);
});
