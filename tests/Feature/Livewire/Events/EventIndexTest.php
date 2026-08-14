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

it('changes an event status', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create(['status' => Event::STATUS_ACTIVE]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('setStatus', $event->id, Event::STATUS_PAUSED);

    expect($event->fresh()->status)->toBe(Event::STATUS_PAUSED);
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

it('links the summary through to an occurrence\'s existing roster page', function () {
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create();
    $occurrence = EventOccurrence::factory()->fromEvent($event)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->call('select', $event->id)
        ->assertSee(route('guilds.event-occurrences.show', [$guild, $occurrence]), false);
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

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(EventIndex::class, ['guild' => $guild])
        ->assertForbidden();
});
