<?php

declare(strict_types=1);

use App\Livewire\EventRoleSets\ManageEventRoleSetRoles;
use App\Models\EventOccurrence;
use App\Models\EventRoleSet;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('adds a role through the component', function () {
    $user = User::factory()->create();
    $roleSet = EventRoleSet::factory()->withRoles(1)->create();
    GuildAdmin::factory()->for($roleSet->guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(ManageEventRoleSetRoles::class, ['roleSet' => $roleSet])
        ->set('newRoleName', 'Fresh Role')
        ->call('addRole')
        ->assertHasNoErrors();

    expect($roleSet->roles()->where('name', 'Fresh Role')->exists())->toBeTrue();
});

it('shows the role set as locked and refuses to add a role while an open occurrence uses it', function () {
    $user = User::factory()->create();
    $roleSet = EventRoleSet::factory()->withRoles(1)->create();
    GuildAdmin::factory()->for($roleSet->guild)->for($user)->create();
    EventOccurrence::factory()
        ->state(['event_role_set_id' => $roleSet->id])
        ->posted()
        ->create(['scheduled_start_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(ManageEventRoleSetRoles::class, ['roleSet' => $roleSet])
        ->assertSee('Locked while an open occurrence')
        ->set('newRoleName', 'Fresh Role')
        ->call('addRole')
        ->assertHasErrors('newRoleName');

    expect($roleSet->roles()->count())->toBe(1);
});
