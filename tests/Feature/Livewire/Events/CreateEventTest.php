<?php

declare(strict_types=1);

use App\Livewire\Events\CreateEvent;
use App\Models\Event;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('creates a one-off event', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->set('title', 'Game Night')
        ->set('description', 'Bring snacks')
        ->set('channelId', '123456')
        ->set('eventRoleSetId', $roleSet->id)
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertDispatched('event-created')
        ->assertHasNoErrors();

    $event = Event::query()->where('title', 'Game Night')->first();
    expect($event)->not->toBeNull()
        ->and($event->occurrences)->toHaveCount(1);
});

it('creates a weekly recurring event with no occurrences yet', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->set('title', 'Raid Night')
        ->set('description', 'Weekly raid')
        ->set('channelId', '123456')
        ->set('eventRoleSetId', $roleSet->id)
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->set('recurrenceType', 'weekly')
        ->set('recurrenceDaysOfWeek', ['WE'])
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::query()->where('title', 'Raid Night')->first();
    expect($event->isRecurring())->toBeTrue()
        ->and($event->occurrences)->toHaveCount(0);
});

it('rejects a weekly recurrence with no days selected', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->set('title', 'Raid Night')
        ->set('description', 'Weekly raid')
        ->set('channelId', '123456')
        ->set('eventRoleSetId', $roleSet->id)
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->set('recurrenceType', 'weekly')
        ->call('save')
        ->assertHasErrors('recurrenceType');

    expect(Event::query()->count())->toBe(0);
});

it('denies mounting the component for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->assertForbidden();
});
