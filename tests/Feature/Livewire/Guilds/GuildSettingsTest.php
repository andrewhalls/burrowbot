<?php

declare(strict_types=1);

use App\Livewire\Guilds\GuildSettings;
use App\Models\DiscordChannel;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('shows the channel picker scoped to this guild, not another guild\'s channels', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    DiscordChannel::factory()->for($guild)->create(['name' => 'announcements']);
    $otherGuild = Guild::factory()->create();
    DiscordChannel::factory()->for($otherGuild)->create(['name' => 'other-guild-general']);

    Livewire::actingAs($user)
        ->test(GuildSettings::class, ['guild' => $guild])
        ->assertSee('#announcements')
        ->assertDontSee('#other-guild-general');
});

it('shows an empty (not broken) channel picker when the guild has no synced channels', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(GuildSettings::class, ['guild' => $guild])
        ->assertSee('No synced channels yet.');
});

it('saves the default channel id', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create(['default_channel_id' => null]);
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(GuildSettings::class, ['guild' => $guild])
        ->set('defaultChannelId', '555666777')
        ->call('save')
        ->assertDispatched('guild-settings-saved');

    expect($guild->fresh()->default_channel_id)->toBe('555666777');
});

it('pre-fills new giveaway drafts with the guild default channel', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create(['default_channel_id' => '111']);
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\Giveaways\CreateGiveaway::class, ['guild' => $guild])
        ->assertSet('channelId', '111');
});

it('denies settings access to a non-admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(GuildSettings::class, ['guild' => $guild])
        ->assertForbidden();
});
