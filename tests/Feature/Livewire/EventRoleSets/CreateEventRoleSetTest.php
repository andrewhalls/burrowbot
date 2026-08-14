<?php

declare(strict_types=1);

use App\Livewire\EventRoleSets\CreateEventRoleSet;
use App\Models\DiscordRole;
use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('creates a role set from synced Discord roles and dispatches an event on success', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $tank = DiscordRole::factory()->for($guild)->create(['name' => 'Tank']);
    $dps = DiscordRole::factory()->for($guild)->create(['name' => 'DPS']);

    Livewire::actingAs($user)
        ->test(CreateEventRoleSet::class, ['guild' => $guild])
        ->set('name', 'Raid Roles')
        ->call('addDiscordRole', $tank->discord_role_id)
        ->call('addDiscordRole', $dps->discord_role_id)
        ->set('roles.0.capacity_mode', 'capped')
        ->set('roles.0.capacity', 2)
        ->call('save')
        ->assertDispatched('event-role-set-created')
        ->assertHasNoErrors();

    $roleSet = EventRoleSet::query()->where('name', 'Raid Roles')->sole();
    expect($roleSet->roles()->pluck('discord_role_id')->all())->toBe([$tank->discord_role_id, $dps->discord_role_id])
        ->and($roleSet->roles()->where('name', 'Tank')->first()->capacity)->toBe(2);
});

it('shows an error and creates nothing when zero roles are selected', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(CreateEventRoleSet::class, ['guild' => $guild])
        ->set('name', 'Empty')
        ->call('save')
        ->assertHasErrors('roles');

    expect(EventRoleSet::query()->count())->toBe(0);
});

it('bulk-adds roles from an existing role set preset', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $healer = DiscordRole::factory()->for($guild)->create(['name' => 'Healer']);
    $tank = DiscordRole::factory()->for($guild)->create(['name' => 'Tank']);
    $preset = EventRoleSet::factory()->for($guild)->create(['name' => 'Existing Roles']);
    EventRole::factory()->for($preset, 'eventRoleSet')->withDiscordRoleId($healer->discord_role_id)->create(['name' => 'Healer']);
    EventRole::factory()->for($preset, 'eventRoleSet')->withDiscordRoleId($tank->discord_role_id)->create(['name' => 'Tank']);

    $component = Livewire::actingAs($user)
        ->test(CreateEventRoleSet::class, ['guild' => $guild])
        ->assertSee('Existing Roles')
        ->call('addRoleSetPreset', $preset->id);

    expect(collect($component->get('roles'))->pluck('discord_role_id')->all())
        ->toEqualCanonicalizing([$healer->discord_role_id, $tank->discord_role_id]);
});

it('does not add the same role twice', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $role = DiscordRole::factory()->for($guild)->create();

    $component = Livewire::actingAs($user)
        ->test(CreateEventRoleSet::class, ['guild' => $guild])
        ->call('addDiscordRole', $role->discord_role_id)
        ->call('addDiscordRole', $role->discord_role_id);

    expect($component->get('roles'))->toHaveCount(1);
});

it('removes a role row', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $role = DiscordRole::factory()->for($guild)->create();

    $component = Livewire::actingAs($user)
        ->test(CreateEventRoleSet::class, ['guild' => $guild])
        ->call('addDiscordRole', $role->discord_role_id)
        ->call('removeRoleRow', 0);

    expect($component->get('roles'))->toBe([]);
});

it('denies mounting the component for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateEventRoleSet::class, ['guild' => $guild])
        ->assertForbidden();
});
