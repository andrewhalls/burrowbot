<?php

declare(strict_types=1);

use App\Actions\Events\MarkNotAttendingAction;
use App\Actions\Events\SignUpForEventRoleAction;
use App\Models\DiscordMember;
use App\Models\EventAttendance;
use App\Models\EventOccurrence;
use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\EventRoleSignup;
use App\Support\Events\SignupResult;

function signUpAction(): SignUpForEventRoleAction
{
    return app(SignUpForEventRoleAction::class);
}

function occurrenceWithRoleSet(EventRoleSet $roleSet, array $overrides = []): EventOccurrence
{
    return EventOccurrence::factory()->create(array_merge([
        'event_role_set_id' => $roleSet->id,
        'scheduled_start_at' => now()->addDay(),
    ], $overrides));
}

it('confirms a signup when the role has capacity remaining', function () {
    $roleSet = EventRoleSet::factory()->create();
    $role = EventRole::factory()->capped(2)->for($roleSet, 'eventRoleSet')->create();
    $occurrence = occurrenceWithRoleSet($roleSet);

    $result = signUpAction()->execute($occurrence, $role, '111', 'entrant');

    expect($result->status)->toBe(SignupResult::STATUS_CONFIRMED)
        ->and(EventRoleSignup::query()->where('is_waitlisted', false)->count())->toBe(1);
});

it('rejects a signup when a capped-blocking role is full', function () {
    $roleSet = EventRoleSet::factory()->create();
    $role = EventRole::factory()->capped(1)->for($roleSet, 'eventRoleSet')->create();
    $occurrence = occurrenceWithRoleSet($roleSet);

    signUpAction()->execute($occurrence, $role, '111', 'first');
    $result = signUpAction()->execute($occurrence, $role, '222', 'second');

    expect($result->status)->toBe(SignupResult::STATUS_REJECTED)
        ->and(EventRoleSignup::query()->count())->toBe(1);
});

it('waitlists a signup when a capped-with-waitlist role is full', function () {
    $roleSet = EventRoleSet::factory()->create();
    $role = EventRole::factory()->waitlisted(1)->for($roleSet, 'eventRoleSet')->create();
    $occurrence = occurrenceWithRoleSet($roleSet);

    signUpAction()->execute($occurrence, $role, '111', 'first');
    $result = signUpAction()->execute($occurrence, $role, '222', 'second');

    expect($result->status)->toBe(SignupResult::STATUS_WAITLISTED);

    $secondMember = DiscordMember::query()->where('discord_user_id', '222')->first();
    $signup = EventRoleSignup::query()->where('discord_member_id', $secondMember->id)->first();
    expect($signup->is_waitlisted)->toBeTrue();
});

it('is idempotent when the member selects the same role twice', function () {
    $roleSet = EventRoleSet::factory()->create();
    $role = EventRole::factory()->for($roleSet, 'eventRoleSet')->create();
    $occurrence = occurrenceWithRoleSet($roleSet);

    signUpAction()->execute($occurrence, $role, '111', 'entrant');
    signUpAction()->execute($occurrence, $role, '111', 'entrant');

    expect(EventRoleSignup::query()->count())->toBe(1);
});

it('replaces the previous role under a single-role policy and frees its capacity', function () {
    $roleSet = EventRoleSet::factory()->create(['allow_multiple_roles' => false]);
    $roleA = EventRole::factory()->capped(1)->for($roleSet, 'eventRoleSet')->create();
    $roleB = EventRole::factory()->for($roleSet, 'eventRoleSet')->create();
    $occurrence = occurrenceWithRoleSet($roleSet);

    signUpAction()->execute($occurrence, $roleA, '111', 'member');
    $result = signUpAction()->execute($occurrence, $roleB, '111', 'member');

    expect($result->status)->toBe(SignupResult::STATUS_CONFIRMED);

    $member = DiscordMember::query()->where('discord_user_id', '111')->first();
    $signups = EventRoleSignup::query()->where('discord_member_id', $member->id)->get();
    expect($signups)->toHaveCount(1)
        ->and($signups->first()->event_role_id)->toBe($roleB->id);
});

it('adds an additional role under a multiple-role policy', function () {
    $roleSet = EventRoleSet::factory()->create(['allow_multiple_roles' => true]);
    $roleA = EventRole::factory()->for($roleSet, 'eventRoleSet')->create();
    $roleB = EventRole::factory()->for($roleSet, 'eventRoleSet')->create();
    $occurrence = occurrenceWithRoleSet($roleSet);

    signUpAction()->execute($occurrence, $roleA, '111', 'member');
    signUpAction()->execute($occurrence, $roleB, '111', 'member');

    $member = DiscordMember::query()->where('discord_user_id', '111')->first();
    expect(EventRoleSignup::query()->where('discord_member_id', $member->id)->count())->toBe(2);
});

it('promotes the earliest waitlisted member when a confirmed slot frees up', function () {
    $roleSet = EventRoleSet::factory()->create(['allow_multiple_roles' => false]);
    $role = EventRole::factory()->waitlisted(1)->for($roleSet, 'eventRoleSet')->create();
    $otherRole = EventRole::factory()->for($roleSet, 'eventRoleSet')->create();
    $occurrence = occurrenceWithRoleSet($roleSet);

    signUpAction()->execute($occurrence, $role, '111', 'first'); // confirmed
    signUpAction()->execute($occurrence, $role, '222', 'second'); // waitlisted

    // First member gives up the role by switching to a different one.
    signUpAction()->execute($occurrence, $otherRole, '111', 'first');

    $secondMember = DiscordMember::query()->where('discord_user_id', '222')->first();
    $secondSignup = EventRoleSignup::query()
        ->where('discord_member_id', $secondMember->id)
        ->where('event_role_id', $role->id)
        ->first();

    expect($secondSignup->is_waitlisted)->toBeFalse();
});

it('rejects a signup after the occurrence has started', function () {
    $roleSet = EventRoleSet::factory()->create();
    $role = EventRole::factory()->for($roleSet, 'eventRoleSet')->create();
    $occurrence = occurrenceWithRoleSet($roleSet, ['scheduled_start_at' => now()->subMinute()]);

    $result = signUpAction()->execute($occurrence, $role, '111', 'entrant');

    expect($result->status)->toBe(SignupResult::STATUS_REJECTED)
        ->and(EventRoleSignup::query()->count())->toBe(0);
});

it('clears Not Attending when the member then selects a role', function () {
    $roleSet = EventRoleSet::factory()->create();
    $role = EventRole::factory()->for($roleSet, 'eventRoleSet')->create();
    $occurrence = occurrenceWithRoleSet($roleSet);

    app(MarkNotAttendingAction::class)->execute($occurrence, '111', 'member');
    signUpAction()->execute($occurrence, $role, '111', 'member');

    $member = DiscordMember::query()->where('discord_user_id', '111')->first();
    $attendance = EventAttendance::query()->where('discord_member_id', $member->id)->first();

    expect($attendance->status)->toBe(EventAttendance::STATUS_ATTENDING)
        ->and(EventRoleSignup::query()->where('discord_member_id', $member->id)->count())->toBe(1);
});

it('rejects a role that does not belong to the occurrence role set', function () {
    $roleSet = EventRoleSet::factory()->create();
    $occurrence = occurrenceWithRoleSet($roleSet);
    $foreignRole = EventRole::factory()->create(); // different role set

    $result = signUpAction()->execute($occurrence, $foreignRole, '111', 'entrant');

    expect($result->status)->toBe(SignupResult::STATUS_REJECTED);
});
