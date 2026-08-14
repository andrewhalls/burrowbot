<?php

declare(strict_types=1);

use App\Actions\Events\UpdateEventAction;
use App\Actions\Events\UpdateEventStatusAction;
use App\Models\Event;
use App\Models\EventOccurrence;
use Illuminate\Support\Facades\Storage;

it('updates event fields without touching existing occurrences', function () {
    $event = Event::factory()->create(['title' => 'Old Title']);
    $occurrence = EventOccurrence::factory()->create(['event_id' => $event->id, 'title' => 'Old Title']);

    (new UpdateEventAction)->execute($event, ['title' => 'New Title']);

    expect($event->fresh()->title)->toBe('New Title')
        ->and($occurrence->fresh()->title)->toBe('Old Title');
});

it('leaves already-generated occurrences\' images unchanged and deletes the orphaned old file when replaced', function () {
    Storage::fake('public');
    Storage::disk('public')->put('event-images/old.jpg', 'old-bytes');
    Storage::disk('public')->put('event-images/new.jpg', 'new-bytes');

    $event = Event::factory()->create(['image_path' => 'event-images/old.jpg']);
    $occurrence = EventOccurrence::factory()->create([
        'event_id' => $event->id,
        'image_path' => 'event-images/old.jpg',
    ]);

    (new UpdateEventAction)->execute($event, ['image_path' => 'event-images/new.jpg']);

    expect($event->fresh()->image_path)->toBe('event-images/new.jpg')
        ->and($occurrence->fresh()->image_path)->toBe('event-images/old.jpg');

    Storage::disk('public')->assertExists('event-images/old.jpg');
});

it('deletes the old event image file once no occurrence references it anymore', function () {
    Storage::fake('public');
    Storage::disk('public')->put('event-images/old.jpg', 'old-bytes');
    Storage::disk('public')->put('event-images/new.jpg', 'new-bytes');

    $event = Event::factory()->create(['image_path' => 'event-images/old.jpg']);

    (new UpdateEventAction)->execute($event, ['image_path' => 'event-images/new.jpg']);

    Storage::disk('public')->assertMissing('event-images/old.jpg');
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
