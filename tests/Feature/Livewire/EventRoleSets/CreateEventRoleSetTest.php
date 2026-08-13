<?php

declare(strict_types=1);

use App\Livewire\EventRoleSets\CreateEventRoleSet;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('creates a role set and dispatches an event on success', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(CreateEventRoleSet::class, ['guild' => $guild])
        ->set('name', 'Raid Roles')
        ->set('roles.0.name', 'Tank')
        ->set('roles.0.capacity_mode', 'capped')
        ->set('roles.0.capacity', 2)
        ->set('roles.1.name', 'DPS')
        ->call('save')
        ->assertDispatched('event-role-set-created')
        ->assertHasNoErrors();

    expect(EventRoleSet::query()->where('name', 'Raid Roles')->exists())->toBeTrue();
});

it('shows an error and creates nothing when every role is blank', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(CreateEventRoleSet::class, ['guild' => $guild])
        ->set('name', 'Empty')
        ->set('roles.0.name', '')
        ->set('roles.1.name', '')
        ->call('save')
        ->assertHasErrors('roles');

    expect(EventRoleSet::query()->count())->toBe(0);
});

it('denies mounting the component for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateEventRoleSet::class, ['guild' => $guild])
        ->assertForbidden();
});
