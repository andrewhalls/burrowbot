<?php

declare(strict_types=1);

use App\Livewire\EventRoleSets\ManageEventRoleSetRoles;
use App\Models\DiscordRole;
use App\Models\EventOccurrence;
use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('adds a synced role through the component, persisting its discord_role_id', function () {
    $user = User::factory()->create();
    $roleSet = EventRoleSet::factory()->withRoles(1)->create();
    GuildAdmin::factory()->for($roleSet->guild)->for($user)->create();
    $role = DiscordRole::factory()->for($roleSet->guild)->create(['name' => 'Fresh Role']);

    Livewire::actingAs($user)
        ->test(ManageEventRoleSetRoles::class, ['roleSet' => $roleSet])
        ->call('addDiscordRole', $role->discord_role_id)
        ->assertHasNoErrors();

    $added = $roleSet->roles()->where('name', 'Fresh Role')->first();
    expect($added)->not->toBeNull()
        ->and($added->discord_role_id)->toBe($role->discord_role_id);
});

it('bulk-adds roles from a preset, all using the currently-selected capacity', function () {
    $user = User::factory()->create();
    $roleSet = EventRoleSet::factory()->withRoles(0)->create();
    GuildAdmin::factory()->for($roleSet->guild)->for($user)->create();
    $roleA = DiscordRole::factory()->for($roleSet->guild)->create();
    $roleB = DiscordRole::factory()->for($roleSet->guild)->create();
    $preset = EventRoleSet::factory()->for($roleSet->guild)->create();
    EventRole::factory()->for($preset, 'eventRoleSet')->withDiscordRoleId($roleA->discord_role_id)->create();
    EventRole::factory()->for($preset, 'eventRoleSet')->withDiscordRoleId($roleB->discord_role_id)->create();

    Livewire::actingAs($user)
        ->test(ManageEventRoleSetRoles::class, ['roleSet' => $roleSet])
        ->set('newRoleCapacityMode', 'capped')
        ->set('newRoleCapacity', 5)
        ->call('addRoleSetPreset', $preset->id)
        ->assertHasNoErrors();

    expect($roleSet->roles()->count())->toBe(2)
        ->and($roleSet->roles()->where('capacity_mode', 'capped')->where('capacity', 5)->count())->toBe(2);
});

it('does not offer the role set itself as a preset, but does offer a different one', function () {
    $user = User::factory()->create();
    $roleSet = EventRoleSet::factory()->withRoles(1)->create();
    GuildAdmin::factory()->for($roleSet->guild)->for($user)->create();
    $otherSet = EventRoleSet::factory()->for($roleSet->guild)->withRoles(1)->create(['name' => 'Other Set']);

    Livewire::actingAs($user)
        ->test(ManageEventRoleSetRoles::class, ['roleSet' => $roleSet])
        ->assertDontSee("addRoleSetPreset({$roleSet->id})", false)
        ->assertSee("addRoleSetPreset({$otherSet->id})", false);
});

it('shows the role set as locked and refuses to add a role while an open occurrence uses it', function () {
    $user = User::factory()->create();
    $roleSet = EventRoleSet::factory()->withRoles(1)->create();
    GuildAdmin::factory()->for($roleSet->guild)->for($user)->create();
    $role = DiscordRole::factory()->for($roleSet->guild)->create();
    EventOccurrence::factory()
        ->state(['event_role_set_id' => $roleSet->id])
        ->posted()
        ->create(['scheduled_start_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(ManageEventRoleSetRoles::class, ['roleSet' => $roleSet])
        ->assertSee('Locked while an open occurrence')
        ->call('addDiscordRole', $role->discord_role_id)
        ->assertHasErrors('newRoleCapacityMode');

    expect($roleSet->roles()->count())->toBe(1);
});
