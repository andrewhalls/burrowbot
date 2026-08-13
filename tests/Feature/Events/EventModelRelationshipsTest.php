<?php

declare(strict_types=1);

use App\Models\DiscordMember;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventOccurrence;
use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\EventRoleSignup;
use App\Models\Guild;
use Illuminate\Database\QueryException;

it('relates a guild to its event role sets and events', function () {
    $guild = Guild::factory()->create();
    EventRoleSet::factory()->for($guild)->create();
    Event::factory()->for($guild)->create();

    expect($guild->eventRoleSets)->toHaveCount(1)
        ->and($guild->events)->toHaveCount(1);
});

it('orders event roles by sort_order', function () {
    $roleSet = EventRoleSet::factory()->create();

    EventRole::factory()->for($roleSet, 'eventRoleSet')->create(['name' => 'Third', 'sort_order' => 2]);
    EventRole::factory()->for($roleSet, 'eventRoleSet')->create(['name' => 'First', 'sort_order' => 0]);
    EventRole::factory()->for($roleSet, 'eventRoleSet')->create(['name' => 'Second', 'sort_order' => 1]);

    expect($roleSet->roles->pluck('name')->all())->toBe(['First', 'Second', 'Third']);
});

it('reports capacity mode helpers correctly', function () {
    $uncapped = EventRole::factory()->create();
    $capped = EventRole::factory()->capped(2)->create();
    $waitlisted = EventRole::factory()->waitlisted(2)->create();

    expect($uncapped->isUncapped())->toBeTrue()
        ->and($capped->isUncapped())->toBeFalse()
        ->and($waitlisted->isWaitlisted())->toBeTrue();
});

it('reports a role set as not editable while an open occurrence uses it', function () {
    $roleSet = EventRoleSet::factory()->create();

    expect($roleSet->isEditable())->toBeTrue();

    EventOccurrence::factory()
        ->state(['event_role_set_id' => $roleSet->id])
        ->posted()
        ->create(['scheduled_start_at' => now()->addDay()]);

    expect($roleSet->fresh()->isEditable())->toBeFalse();
});

it('reports a role set as editable once its occurrence has started', function () {
    $roleSet = EventRoleSet::factory()->create();

    EventOccurrence::factory()
        ->state(['event_role_set_id' => $roleSet->id])
        ->posted()
        ->started()
        ->create();

    expect($roleSet->isEditable())->toBeTrue();
});

it('reports whether an occurrence has started based on scheduled_start_at, not status', function () {
    $future = EventOccurrence::factory()->create(['scheduled_start_at' => now()->addHour()]);
    $past = EventOccurrence::factory()->posted()->create(['scheduled_start_at' => now()->subMinute()]);

    expect($future->hasStarted())->toBeFalse()
        ->and($past->hasStarted())->toBeTrue();
});

it('enforces one attendance row per occurrence and member', function () {
    $occurrence = EventOccurrence::factory()->create();
    $member = DiscordMember::factory()->create();

    EventAttendance::factory()->for($occurrence, 'eventOccurrence')->for($member, 'discordMember')->create();

    expect(fn () => EventAttendance::factory()->for($occurrence, 'eventOccurrence')->for($member, 'discordMember')->create())
        ->toThrow(QueryException::class);
});

it('enforces one role signup row per occurrence, member, and role', function () {
    $occurrence = EventOccurrence::factory()->create();
    $member = DiscordMember::factory()->create();
    $role = EventRole::factory()->create();

    EventRoleSignup::factory()->for($occurrence, 'eventOccurrence')->for($member, 'discordMember')->for($role, 'eventRole')->create();

    expect(fn () => EventRoleSignup::factory()->for($occurrence, 'eventOccurrence')->for($member, 'discordMember')->for($role, 'eventRole')->create())
        ->toThrow(QueryException::class);
});

it('allows the same member to hold two different roles on the same occurrence', function () {
    $occurrence = EventOccurrence::factory()->create();
    $member = DiscordMember::factory()->create();
    $roleA = EventRole::factory()->create();
    $roleB = EventRole::factory()->create();

    EventRoleSignup::factory()->for($occurrence, 'eventOccurrence')->for($member, 'discordMember')->for($roleA, 'eventRole')->create();
    EventRoleSignup::factory()->for($occurrence, 'eventOccurrence')->for($member, 'discordMember')->for($roleB, 'eventRole')->create();

    expect($occurrence->roleSignups)->toHaveCount(2);
});
