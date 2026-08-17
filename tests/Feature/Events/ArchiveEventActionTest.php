<?php

declare(strict_types=1);

use App\Actions\Events\ArchiveEventAction;
use App\Actions\Events\UnarchiveEventAction;
use App\Models\Event;

it('archives an active event, cancelling it and stamping archived_at', function () {
    $event = Event::factory()->create(['status' => Event::STATUS_ACTIVE]);

    (new ArchiveEventAction)->execute($event);

    expect($event->fresh()->status)->toBe(Event::STATUS_CANCELLED)
        ->and($event->fresh()->archived_at)->not->toBeNull();
});

it('archives a paused event, cancelling it and stamping archived_at', function () {
    $event = Event::factory()->paused()->create();

    (new ArchiveEventAction)->execute($event);

    expect($event->fresh()->status)->toBe(Event::STATUS_CANCELLED)
        ->and($event->fresh()->archived_at)->not->toBeNull();
});

it('archives an already-cancelled event without erroring, stamping archived_at', function () {
    $event = Event::factory()->cancelled()->create();

    (new ArchiveEventAction)->execute($event);

    expect($event->fresh()->status)->toBe(Event::STATUS_CANCELLED)
        ->and($event->fresh()->archived_at)->not->toBeNull();
});

it('unarchiving clears archived_at only, leaving status untouched', function () {
    $event = Event::factory()->archived()->create();

    (new UnarchiveEventAction)->execute($event);

    expect($event->fresh()->archived_at)->toBeNull()
        ->and($event->fresh()->status)->toBe(Event::STATUS_CANCELLED);
});
