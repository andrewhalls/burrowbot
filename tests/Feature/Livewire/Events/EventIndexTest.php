<?php

declare(strict_types=1);

use App\Livewire\Events\EventIndex;
use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('lists events for the guild', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create(['title' => 'Game Night']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->assertSee('Game Night');
});

it('shows an event\'s image on its tile and in the summary panel when set', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->withImage('event-images/abc.jpg')->create(['title' => 'Game Night']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->assertSee($event->image_url, false)
        ->call('select', $event->id)
        ->assertSee($event->image_url, false);
});

it('changes an event status', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create(['status' => Event::STATUS_ACTIVE]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('setStatus', $event->id, Event::STATUS_PAUSED);

    expect($event->fresh()->status)->toBe(Event::STATUS_PAUSED);
});

it('excludes archived events from the list by default, and shows them when Show archived is toggled on', function () {
    $guild = Guild::factory()->create();
    $active = Event::factory()->for($guild)->create(['title' => 'Active Event']);
    $archived = Event::factory()->for($guild)->archived()->create(['title' => 'Old Event']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->assertSee('Active Event')
        ->assertDontSee('Old Event')
        ->set('showArchived', true)
        ->assertSee('Active Event')
        ->assertSee('Old Event');
});

it('archives an event, cancelling it and hiding it from the default list', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create(['status' => Event::STATUS_ACTIVE]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('archive', $event->id);

    expect($event->fresh()->status)->toBe(Event::STATUS_CANCELLED)
        ->and($event->fresh()->archived_at)->not->toBeNull();
});

it('unarchives an event, leaving its status untouched', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->archived()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('unarchive', $event->id);

    expect($event->fresh()->archived_at)->toBeNull()
        ->and($event->fresh()->status)->toBe(Event::STATUS_CANCELLED);
});

it('offers Edit and Unarchive for an archived event once shown', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->archived()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->set('showArchived', true)
        ->call('select', $event->id)
        ->assertSeeHtml('wire:click="toggleEdit"')
        ->assertSeeHtml('wire:click.stop="unarchive('.$event->id.')"');
});

it('refuses to archive an event belonging to a different guild', function () {
    $guild = Guild::factory()->create();
    $otherGuild = Guild::factory()->create();
    $otherEvent = Event::factory()->for($otherGuild)->create();
    $staff = actingEventStaffFor($guild);

    expect(fn () => Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('archive', $otherEvent->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('shows who created an event on its tile and in the summary panel', function () {
    $guild = Guild::factory()->create();
    $creator = User::factory()->create(['name' => 'Ada Admin']);
    $event = Event::factory()->for($guild)->createdBy($creator)->create(['title' => 'Raid Night']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->assertSee('Created by Ada Admin')
        ->call('select', $event->id)
        ->assertSee('Created by Ada Admin');
});

it('deletes a series with no posted occurrences', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('select', $event->id)
        ->call('delete')
        ->assertSet('selectedId', null);

    expect(Event::query()->find($event->id))->toBeNull();
});

it('does not offer delete, and rejects it server-side, once a series has a posted occurrence', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create();
    EventOccurrence::factory()->posted()->create(['event_id' => $event->id]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('select', $event->id)
        ->assertDontSeeHtml('wire:click="delete"')
        ->call('delete')
        ->assertHasErrors('delete');

    expect(Event::query()->find($event->id))->not->toBeNull();
});

it('toggles into and out of the edit form for the selected event', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('select', $event->id)
        ->assertDontSeeLivewire('events.edit-event')
        ->call('toggleEdit')
        ->assertSeeLivewire('events.edit-event')
        ->call('toggleEdit')
        ->assertDontSeeLivewire('events.edit-event');
});

it('shows the event summary in the detail panel when a tile is selected', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create(['title' => 'Raid Night', 'description' => 'Weekly raid']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->assertSee('Select an item from the list')
        ->call('select', $event->id)
        ->assertDontSee('Select an item from the list')
        ->assertSee('Weekly raid');
});

it('shows an occurrence\'s roster inline in the panel when selected from the summary', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create(['title' => 'Raid Night']);
    $occurrence = EventOccurrence::factory()->fromEvent($event)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('select', $event->id)
        ->assertDontSeeLivewire('events.occurrence-roster')
        ->call('selectOccurrence', $occurrence->id)
        ->assertSeeLivewire('events.occurrence-roster')
        ->assertSee('Not attending')
        ->assertSee('Back to Raid Night');
});

it('returns to the event summary when backing out of a roster', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create(['description' => 'Weekly raid']);
    $occurrence = EventOccurrence::factory()->fromEvent($event)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('select', $event->id)
        ->call('selectOccurrence', $occurrence->id)
        ->call('deselectOccurrence')
        ->assertSee('Weekly raid');
});

it('refuses to select an occurrence belonging to a different event', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create();
    $otherEvent = Event::factory()->for($guild)->create();
    $occurrence = EventOccurrence::factory()->fromEvent($otherEvent)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('select', $event->id)
        ->call('selectOccurrence', $occurrence->id)
        ->assertDontSeeLivewire('events.occurrence-roster');
});

it('returns to the list-only view on deselect', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('select', $event->id)
        ->call('deselect')
        ->assertSee('Select an item from the list');
});

it('refuses to select an event belonging to a different guild', function () {
    $guild = Guild::factory()->create();
    $otherGuild = Guild::factory()->create();
    $otherEvent = Event::factory()->for($otherGuild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('select', $otherEvent->id)
        ->assertSee('Select an item from the list');
});

it('opening the create form deselects the current event, and selecting a tile closes the create form', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    $component = Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('select', $event->id)
        ->call('toggleCreateForm');

    $component->assertSet('selectedId', null)
        ->assertSeeLivewire('events.create-event');

    $component->call('select', $event->id)
        ->assertSet('showCreateForm', false)
        ->assertDontSeeLivewire('events.create-event');
});

it('selects the newly created event after submitting the create form', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    $component = Livewire::actingAs($staff)->test(EventIndex::class, ['guild' => $guild]);
    $component->call('toggleCreateForm');

    $event = Event::factory()->for($guild)->create();
    $component->dispatch('event-created', eventId: $event->id);

    $component->assertSet('showCreateForm', false)
        ->assertSet('selectedId', $event->id);
});

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(EventIndex::class, ['guild' => $guild])
        ->assertForbidden();
});
