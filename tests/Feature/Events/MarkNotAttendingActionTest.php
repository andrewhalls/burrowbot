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

function notAttendingAction(): MarkNotAttendingAction
{
    return app(MarkNotAttendingAction::class);
}

it('clears a confirmed role signup and records not attending', function () {
    $roleSet = EventRoleSet::factory()->create();
    $role = EventRole::factory()->for($roleSet, 'eventRoleSet')->create();
    $occurrence = EventOccurrence::factory()->create(['event_role_set_id' => $roleSet->id, 'scheduled_start_at' => now()->addDay()]);

    app(SignUpForEventRoleAction::class)->execute($occurrence, $role, '111', 'member');
    $result = notAttendingAction()->execute($occurrence, '111', 'member');

    expect($result->status)->toBe(SignupResult::STATUS_NOT_ATTENDING);

    $member = DiscordMember::query()->where('discord_user_id', '111')->first();
    expect(EventRoleSignup::query()->where('discord_member_id', $member->id)->count())->toBe(0)
        ->and(EventAttendance::query()->where('discord_member_id', $member->id)->first()->status)
        ->toBe(EventAttendance::STATUS_NOT_ATTENDING);
});

it('promotes a waitlisted member when the not-attending member held a confirmed slot', function () {
    $roleSet = EventRoleSet::factory()->create();
    $role = EventRole::factory()->waitlisted(1)->for($roleSet, 'eventRoleSet')->create();
    $occurrence = EventOccurrence::factory()->create(['event_role_set_id' => $roleSet->id, 'scheduled_start_at' => now()->addDay()]);

    app(SignUpForEventRoleAction::class)->execute($occurrence, $role, '111', 'first');
    app(SignUpForEventRoleAction::class)->execute($occurrence, $role, '222', 'second');

    notAttendingAction()->execute($occurrence, '111', 'first');

    $secondMember = DiscordMember::query()->where('discord_user_id', '222')->first();
    $secondSignup = EventRoleSignup::query()->where('discord_member_id', $secondMember->id)->first();
    expect($secondSignup->is_waitlisted)->toBeFalse();
});

it('marks not attending for a member with no prior role signup', function () {
    $occurrence = EventOccurrence::factory()->create(['scheduled_start_at' => now()->addDay()]);

    $result = notAttendingAction()->execute($occurrence, '111', 'member');

    expect($result->status)->toBe(SignupResult::STATUS_NOT_ATTENDING);
});

it('rejects marking not attending after the occurrence has started', function () {
    $occurrence = EventOccurrence::factory()->create(['scheduled_start_at' => now()->subMinute()]);

    $result = notAttendingAction()->execute($occurrence, '111', 'member');

    expect($result->status)->toBe(SignupResult::STATUS_REJECTED);
});
