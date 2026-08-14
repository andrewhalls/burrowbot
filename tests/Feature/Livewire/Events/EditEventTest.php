<?php

declare(strict_types=1);

use App\Livewire\Events\EditEvent;
use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('pre-fills the form from the existing event', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $event = Event::factory()->for($guild)->for($roleSet, 'eventRoleSet')->create([
        'title' => 'Raid Night',
        'channel_id' => '999888777',
    ]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditEvent::class, ['event' => $event])
        ->assertSet('title', 'Raid Night')
        ->assertSet('channelId', '999888777')
        ->assertSet('eventRoleSetId', $roleSet->id);
});

it('pre-fills recurrence fields by parsing the stored rrule', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)
        ->recurring('FREQ=WEEKLY;INTERVAL=1;BYDAY=WE', now()->next('Wednesday')->setTime(20, 0), 'UTC')
        ->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditEvent::class, ['event' => $event])
        ->assertSet('recurrenceType', 'weekly')
        ->assertSet('recurrenceDaysOfWeek', ['WE']);
});

it('saves changes to an event', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $event = Event::factory()->for($guild)->for($roleSet, 'eventRoleSet')->create(['title' => 'Old Title']);
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(EditEvent::class, ['event' => $event])
        ->set('title', 'New Title')
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertDispatched('event-updated')
        ->assertHasNoErrors();

    expect($event->fresh()->title)->toBe('New Title');
});

it('leaves already-generated occurrences unaffected by an edit', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $event = Event::factory()->for($guild)->for($roleSet, 'eventRoleSet')->create(['title' => 'Old Title']);
    $occurrence = EventOccurrence::factory()->create(['event_id' => $event->id, 'title' => 'Old Title']);
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(EditEvent::class, ['event' => $event])
        ->set('title', 'New Title')
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertHasNoErrors();

    expect($occurrence->fresh()->title)->toBe('Old Title');
});

it('sets an image and keeps it on subsequent saves that don\'t upload a new one', function () {
    Storage::fake('public');

    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $event = Event::factory()->for($guild)->for($roleSet, 'eventRoleSet')->create();
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(EditEvent::class, ['event' => $event])
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->set('image', UploadedFile::fake()->image('event.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $imagePath = $event->fresh()->image_path;
    expect($imagePath)->not->toBeNull();
    Storage::disk('public')->assertExists($imagePath);

    Livewire::actingAs($staff)
        ->test(EditEvent::class, ['event' => $event->fresh()])
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertHasNoErrors();

    expect($event->fresh()->image_path)->toBe($imagePath);
});

it('denies mounting for a guild the user does not admin', function () {
    $event = Event::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditEvent::class, ['event' => $event])
        ->assertForbidden();
});
