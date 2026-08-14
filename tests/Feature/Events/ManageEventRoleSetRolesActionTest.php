<?php

declare(strict_types=1);

use App\Actions\EventRoleSets\ManageEventRoleSetRolesAction;
use App\Models\EventOccurrence;
use App\Models\EventRole;
use App\Models\EventRoleSet;

it('adds a role to an editable role set', function () {
    $roleSet = EventRoleSet::factory()->withRoles(1)->create();

    $role = (new ManageEventRoleSetRolesAction)->addRole($roleSet, 'Support', null, 'uncapped', null);

    expect($role->name)->toBe('Support')
        ->and($roleSet->roles()->count())->toBe(2);
});

it('stores the discord_role_id on the added role', function () {
    $roleSet = EventRoleSet::factory()->withRoles(1)->create();

    $role = (new ManageEventRoleSetRolesAction)->addRole($roleSet, 'Support', '111', 'uncapped', null);

    expect($role->discord_role_id)->toBe('111');
});

it('removes a role from an editable role set', function () {
    $roleSet = EventRoleSet::factory()->withRoles(2)->create();
    $role = $roleSet->roles->first();

    (new ManageEventRoleSetRolesAction)->removeRole($roleSet, $role);

    expect(EventRole::query()->find($role->id))->toBeNull();
});

it('blocks adding a role while an open occurrence uses the role set', function () {
    $roleSet = EventRoleSet::factory()->withRoles(1)->create();
    EventOccurrence::factory()
        ->state(['event_role_set_id' => $roleSet->id])
        ->posted()
        ->create(['scheduled_start_at' => now()->addDay()]);

    expect(fn () => (new ManageEventRoleSetRolesAction)->addRole($roleSet, 'Support', null, 'uncapped', null))
        ->toThrow(InvalidArgumentException::class);

    expect($roleSet->roles()->count())->toBe(1);
});

it('rejects a capped role with no capacity', function () {
    $roleSet = EventRoleSet::factory()->withRoles(1)->create();

    expect(fn () => (new ManageEventRoleSetRolesAction)->addRole($roleSet, 'Support', null, 'capped', null))
        ->toThrow(InvalidArgumentException::class);
});

it('allows editing again once the occurrence using the role set has started', function () {
    $roleSet = EventRoleSet::factory()->withRoles(1)->create();
    EventOccurrence::factory()
        ->state(['event_role_set_id' => $roleSet->id])
        ->posted()
        ->started()
        ->create();

    $role = (new ManageEventRoleSetRolesAction)->addRole($roleSet, 'Support', null, 'uncapped', null);

    expect($role->exists)->toBeTrue();
});
