<?php

declare(strict_types=1);

use App\Actions\Events\DeleteEventAction;
use App\Models\Event;
use App\Models\EventOccurrence;

it('deletes a series with no occurrences yet', function () {
    $event = Event::factory()->create();

    (new DeleteEventAction)->execute($event);

    expect(Event::query()->find($event->id))->toBeNull();
});

it('deletes a series whose occurrences are all still scheduled, cascading the occurrences', function () {
    $event = Event::factory()->create();
    $occurrence = EventOccurrence::factory()->create(['event_id' => $event->id, 'status' => EventOccurrence::STATUS_SCHEDULED]);

    (new DeleteEventAction)->execute($event);

    expect(Event::query()->find($event->id))->toBeNull()
        ->and(EventOccurrence::query()->find($occurrence->id))->toBeNull();
});

it('rejects deleting a series with a posted occurrence', function () {
    $event = Event::factory()->create();
    EventOccurrence::factory()->posted()->create(['event_id' => $event->id]);

    expect(fn () => (new DeleteEventAction)->execute($event))
        ->toThrow(InvalidArgumentException::class);

    expect(Event::query()->find($event->id))->not->toBeNull();
});
