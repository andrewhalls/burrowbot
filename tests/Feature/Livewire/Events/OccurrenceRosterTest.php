<?php

declare(strict_types=1);

use App\Livewire\Events\OccurrenceRoster;
use App\Models\DiscordMember;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventOccurrence;
use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\EventRoleSignup;
use App\Models\User;
use Livewire\Livewire;

function occurrenceWithRoster(): array
{
    $roleSet = EventRoleSet::factory()->create();
    $tank = EventRole::factory()->capped(1)->for($roleSet, 'eventRoleSet')->create(['name' => 'Tank']);
    $healer = EventRole::factory()->waitlisted(1)->for($roleSet, 'eventRoleSet')->create(['name' => 'Healer']);
    $event = Event::factory()->for($roleSet, 'eventRoleSet')->create();
    $occurrence = EventOccurrence::factory()->create([
        'event_id' => $event->id,
        'event_role_set_id' => $roleSet->id,
    ]);

    $confirmedMember = DiscordMember::factory()->for($occurrence->event->guild)->create(['username' => 'ZeldaTank']);
    $waitlistedMember = DiscordMember::factory()->for($occurrence->event->guild)->create(['username' => 'LinkHealer']);
    $notAttendingMember = DiscordMember::factory()->for($occurrence->event->guild)->create(['username' => 'GanonSkip']);

    EventRoleSignup::factory()->for($occurrence, 'eventOccurrence')->for($confirmedMember, 'discordMember')->for($tank, 'eventRole')->create();
    EventRoleSignup::factory()->for($occurrence, 'eventOccurrence')->for($waitlistedMember, 'discordMember')->for($healer, 'eventRole')->waitlisted()->create();
    EventAttendance::factory()->for($occurrence, 'eventOccurrence')->for($notAttendingMember, 'discordMember')->notAttending()->create();

    return compact('occurrence', 'confirmedMember', 'waitlistedMember', 'notAttendingMember', 'tank', 'healer');
}

it('shows confirmed, waitlisted, and not-attending members', function () {
    ['occurrence' => $occurrence, 'confirmedMember' => $confirmedMember, 'waitlistedMember' => $waitlistedMember, 'notAttendingMember' => $notAttendingMember] = occurrenceWithRoster();
    $staff = actingEventStaffFor($occurrence->event->guild);

    Livewire::actingAs($staff)
        ->test(OccurrenceRoster::class, ['occurrence' => $occurrence])
        ->assertSee($confirmedMember->username)
        ->assertSee($waitlistedMember->username)
        ->assertSee($notAttendingMember->username);
});

it('filters the roster by search term', function () {
    ['occurrence' => $occurrence, 'confirmedMember' => $confirmedMember, 'waitlistedMember' => $waitlistedMember] = occurrenceWithRoster();
    $staff = actingEventStaffFor($occurrence->event->guild);

    Livewire::actingAs($staff)
        ->test(OccurrenceRoster::class, ['occurrence' => $occurrence])
        ->set('search', 'zelda')
        ->assertSee($confirmedMember->username)
        ->assertDontSee($waitlistedMember->username);
});

it('denies access to a user who does not admin the occurrence guild', function () {
    ['occurrence' => $occurrence] = occurrenceWithRoster();
    $outsider = User::factory()->create();

    Livewire::actingAs($outsider)
        ->test(OccurrenceRoster::class, ['occurrence' => $occurrence])
        ->assertForbidden();
});
