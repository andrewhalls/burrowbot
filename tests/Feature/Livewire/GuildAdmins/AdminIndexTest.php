<?php

declare(strict_types=1);

use App\Livewire\GuildAdmins\AdminIndex;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

it('lists full admins with no revoke control', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->discordSynced()->create();

    Livewire::actingAs($user)
        ->test(AdminIndex::class, ['guild' => $guild])
        ->assertSee($user->name)
        ->assertSee('Full admin')
        ->assertDontSeeHtml('wire:click="revoke('.$user->guildAdmins()->first()->id.')"');
});

it('lists granted admins with their sections and a revoke control', function () {
    $fullAdmin = User::factory()->create();
    $scopedUser = User::factory()->create(['name' => 'Scoped Sam']);
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($fullAdmin)->discordSynced()->create();
    $scopedAdmin = GuildAdmin::factory()->for($guild)->for($scopedUser)->granted(['giveaways'])->create();

    Livewire::actingAs($fullAdmin)
        ->test(AdminIndex::class, ['guild' => $guild])
        ->assertSee('Scoped Sam')
        ->assertSee('Popup giveaways')
        ->assertSeeHtml('wire:click="revoke('.$scopedAdmin->id.')"');
});

it('shows a pending grant with no linked user as "Pending invite"', function () {
    $fullAdmin = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($fullAdmin)->discordSynced()->create();
    GuildAdmin::factory()->for($guild)->pending('999888777', ['broadcasts'])->create();

    Livewire::actingAs($fullAdmin)
        ->test(AdminIndex::class, ['guild' => $guild])
        ->assertSee('Pending invite');
});

it('revokes a granted admin', function () {
    $fullAdmin = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($fullAdmin)->discordSynced()->create();
    $scopedAdmin = GuildAdmin::factory()->for($guild)->granted(['giveaways'])->create();

    Livewire::actingAs($fullAdmin)
        ->test(AdminIndex::class, ['guild' => $guild])
        ->call('revoke', $scopedAdmin->id);

    expect(GuildAdmin::query()->find($scopedAdmin->id))->toBeNull();
});

it('toggles into and out of the edit form for a granted admin', function () {
    $fullAdmin = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($fullAdmin)->discordSynced()->create();
    $scopedAdmin = GuildAdmin::factory()->for($guild)->granted(['giveaways'])->create();

    Livewire::actingAs($fullAdmin)
        ->test(AdminIndex::class, ['guild' => $guild])
        ->assertDontSeeLivewire('guild-admins.edit-guild-admin-sections')
        ->call('startEditing', $scopedAdmin->id)
        ->assertSeeLivewire('guild-admins.edit-guild-admin-sections');
});

it('denies mounting for a scoped admin, regardless of granted sections', function () {
    $scopedUser = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($scopedUser)->granted(['giveaways', 'broadcasts', 'events', 'settings'])->create();

    Livewire::actingAs($scopedUser)
        ->test(AdminIndex::class, ['guild' => $guild])
        ->assertForbidden();
});

it('denies mounting for a non-admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(AdminIndex::class, ['guild' => $guild])
        ->assertForbidden();
});

it('refuses to revoke an admin belonging to a different guild', function () {
    $fullAdmin = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($fullAdmin)->discordSynced()->create();
    $otherGuild = Guild::factory()->create();
    $otherAdmin = GuildAdmin::factory()->for($otherGuild)->granted(['giveaways'])->create();

    expect(fn () => Livewire::actingAs($fullAdmin)
        ->test(AdminIndex::class, ['guild' => $guild])
        ->call('revoke', $otherAdmin->id))
        ->toThrow(ModelNotFoundException::class);
});
