<?php

declare(strict_types=1);

use App\Livewire\GuildAdmins\InviteGuildAdmin;
use App\Models\DiscordMember;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('searches only the current guild\'s synced members', function () {
    $fullAdmin = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($fullAdmin)->discordSynced()->create();
    DiscordMember::factory()->for($guild)->create(['username' => 'alice']);
    $otherGuild = Guild::factory()->create();
    DiscordMember::factory()->for($otherGuild)->create(['username' => 'alice-other-guild']);

    Livewire::actingAs($fullAdmin)
        ->test(InviteGuildAdmin::class, ['guild' => $guild])
        ->set('search', 'alice')
        ->assertSee('alice')
        ->assertDontSee('alice-other-guild');
});

it('a member not yet synced cannot be found', function () {
    $fullAdmin = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($fullAdmin)->discordSynced()->create();

    Livewire::actingAs($fullAdmin)
        ->test(InviteGuildAdmin::class, ['guild' => $guild])
        ->set('search', 'nobody')
        ->assertSee('No synced members match.');
});

it('grants the selected member the chosen sections', function () {
    $fullAdmin = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($fullAdmin)->discordSynced()->create();
    $member = DiscordMember::factory()->for($guild)->create();

    Livewire::actingAs($fullAdmin)
        ->test(InviteGuildAdmin::class, ['guild' => $guild])
        ->call('selectMember', $member->id)
        ->set('sections', ['giveaways', 'broadcasts'])
        ->call('save')
        ->assertDispatched('guild-admin-granted')
        ->assertHasNoErrors();

    $admin = GuildAdmin::query()->where('discord_user_id', $member->discord_user_id)->sole();
    expect($admin->sections)->toBe(['giveaways', 'broadcasts']);
});

it('rejects granting with no sections selected', function () {
    $fullAdmin = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($fullAdmin)->discordSynced()->create();
    $member = DiscordMember::factory()->for($guild)->create();

    Livewire::actingAs($fullAdmin)
        ->test(InviteGuildAdmin::class, ['guild' => $guild])
        ->call('selectMember', $member->id)
        ->call('save')
        ->assertHasErrors('sections');

    expect(GuildAdmin::query()->where('discord_user_id', $member->discord_user_id)->exists())->toBeFalse();
});

it('denies mounting for a scoped admin', function () {
    $scopedUser = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($scopedUser)->granted(['giveaways'])->create();

    Livewire::actingAs($scopedUser)
        ->test(InviteGuildAdmin::class, ['guild' => $guild])
        ->assertForbidden();
});
