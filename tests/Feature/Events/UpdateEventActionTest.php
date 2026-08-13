<?php

declare(strict_types=1);

use App\Actions\Events\UpdateEventAction;
use App\Actions\Events\UpdateEventStatusAction;
use App\Models\Event;
use App\Models\EventOccurrence;

it('updates event fields without touching existing occurrences', function () {
    $event = Event::factory()->create(['title' => 'Old Title']);
    $occurrence = EventOccurrence::factory()->create(['event_id' => $event->id, 'title' => 'Old Title']);

    (new UpdateEventAction)->execute($event, ['title' => 'New Title']);

    expect($event->fresh()->title)->toBe('New Title')
        ->and($occurrence->fresh()->title)->toBe('Old Title');
});

it('transitions event status', function () {
    $event = Event::factory()->create(['status' => Event::STATUS_ACTIVE]);

    (new UpdateEventStatusAction)->execute($event, Event::STATUS_PAUSED);

    expect($event->fresh()->status)->toBe(Event::STATUS_PAUSED);
});

it('rejects an invalid status', function () {
    $event = Event::factory()->create();

    expect(fn () => (new UpdateEventStatusAction)->execute($event, 'bogus'))
        ->toThrow(InvalidArgumentException::class);
});
