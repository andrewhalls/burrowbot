<?php

declare(strict_types=1);

use App\Livewire\Events\EventIndex;
use App\Models\Event;
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

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(EventIndex::class, ['guild' => $guild])
        ->assertForbidden();
});
