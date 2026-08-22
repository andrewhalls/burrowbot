<?php

declare(strict_types=1);

use App\Livewire\GuildAdmins\EditGuildAdminSections;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('pre-fills the currently granted sections', function () {
    $fullAdmin = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($fullAdmin)->discordSynced()->create();
    $scopedAdmin = GuildAdmin::factory()->for($guild)->granted(['giveaways', 'broadcasts'])->create();

    Livewire::actingAs($fullAdmin)
        ->test(EditGuildAdminSections::class, ['admin' => $scopedAdmin])
        ->assertSet('sections', ['giveaways', 'broadcasts']);
});

it('saves an updated section list', function () {
    $fullAdmin = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($fullAdmin)->discordSynced()->create();
    $scopedAdmin = GuildAdmin::factory()->for($guild)->granted(['giveaways'])->create();

    Livewire::actingAs($fullAdmin)
        ->test(EditGuildAdminSections::class, ['admin' => $scopedAdmin])
        ->set('sections', ['events'])
        ->call('save')
        ->assertDispatched('guild-admin-sections-updated')
        ->assertHasNoErrors();

    expect($scopedAdmin->fresh()->sections)->toBe(['events']);
});

it('denies mounting for a scoped admin, even for their own row', function () {
    $scopedUser = User::factory()->create();
    $guild = Guild::factory()->create();
    $scopedAdmin = GuildAdmin::factory()->for($guild)->for($scopedUser)->granted(['giveaways'])->create();

    Livewire::actingAs($scopedUser)
        ->test(EditGuildAdminSections::class, ['admin' => $scopedAdmin])
        ->assertForbidden();
});
