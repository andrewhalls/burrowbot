<?php

declare(strict_types=1);

use App\Livewire\EventRoleSets\EventRoleSetIndex;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('lists event role sets for the guild with their role count', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    EventRoleSet::factory()->for($guild)->withRoles(3)->create(['name' => 'Raid Roles']);

    Livewire::actingAs($user)
        ->test(EventRoleSetIndex::class, ['guild' => $guild])
        ->assertSee('Raid Roles')
        ->assertSee('3 roles');
});

it('shows only the selected role set\'s roles in the detail panel', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $setA = EventRoleSet::factory()->for($guild)->create(['name' => 'Set A']);
    $roleA = $setA->roles()->create(['name' => 'Role A', 'sort_order' => 0]);
    $setB = EventRoleSet::factory()->for($guild)->create(['name' => 'Set B']);
    $roleB = $setB->roles()->create(['name' => 'Role B', 'sort_order' => 0]);

    // removeRole({id}) is unique per roster row, unlike the role/set name
    // text which can also legitimately appear as a preset button/tooltip
    // for the *other* role set shown in the same panel's picker.
    $component = Livewire::actingAs($user)
        ->test(EventRoleSetIndex::class, ['guild' => $guild])
        ->assertSee('Select an item from the list')
        ->call('select', $setA->id);

    $component->assertSee("removeRole({$roleA->id})", false)->assertDontSee("removeRole({$roleB->id})", false);

    $component->call('select', $setB->id)
        ->assertSee("removeRole({$roleB->id})", false)
        ->assertDontSee("removeRole({$roleA->id})", false);
});

it('returns to the list-only view on deselect', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();

    Livewire::actingAs($user)
        ->test(EventRoleSetIndex::class, ['guild' => $guild])
        ->call('select', $roleSet->id)
        ->call('deselect')
        ->assertSee('Select an item from the list');
});

it('refuses to select a role set belonging to a different guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $otherGuild = Guild::factory()->create();
    $otherRoleSet = EventRoleSet::factory()->for($otherGuild)->withRoles(1)->create();

    Livewire::actingAs($user)
        ->test(EventRoleSetIndex::class, ['guild' => $guild])
        ->call('select', $otherRoleSet->id)
        ->assertSee('Select an item from the list');
});

it('opening the create form deselects the current role set, and selecting a tile closes the create form', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();

    $component = Livewire::actingAs($user)
        ->test(EventRoleSetIndex::class, ['guild' => $guild])
        ->call('select', $roleSet->id)
        ->call('toggleCreateForm');

    $component->assertSet('selectedId', null)
        ->assertSeeLivewire('event-role-sets.create-event-role-set');

    $component->call('select', $roleSet->id)
        ->assertSet('showCreateForm', false)
        ->assertDontSeeLivewire('event-role-sets.create-event-role-set');
});

it('selects the newly created role set after submitting the create form', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    $component = Livewire::actingAs($user)->test(EventRoleSetIndex::class, ['guild' => $guild]);
    $component->call('toggleCreateForm');

    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $component->dispatch('event-role-set-created', roleSetId: $roleSet->id);

    $component->assertSet('showCreateForm', false)
        ->assertSet('selectedId', $roleSet->id);
});

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(EventRoleSetIndex::class, ['guild' => $guild])
        ->assertForbidden();
});
