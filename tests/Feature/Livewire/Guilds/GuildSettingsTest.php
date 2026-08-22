<?php

declare(strict_types=1);

use App\Livewire\Giveaways\CreateGiveaway;
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

it('defaults the popup giveaway winner-message flag to enabled for a guild that has never touched the setting', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(GuildSettings::class, ['guild' => $guild])
        ->assertSet('popupGiveawayWinnerMessagesEnabled', true);

    expect($guild->popup_giveaway_winner_messages_enabled)->toBeTrue();
});

it('toggles the popup giveaway winner-message flag off and back on', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(GuildSettings::class, ['guild' => $guild])
        ->set('popupGiveawayWinnerMessagesEnabled', false)
        ->call('save')
        ->assertDispatched('guild-settings-saved');

    expect($guild->fresh()->popup_giveaway_winner_messages_enabled)->toBeFalse();

    Livewire::actingAs($user)
        ->test(GuildSettings::class, ['guild' => $guild->fresh()])
        ->assertSet('popupGiveawayWinnerMessagesEnabled', false)
        ->set('popupGiveawayWinnerMessagesEnabled', true)
        ->call('save');

    expect($guild->fresh()->popup_giveaway_winner_messages_enabled)->toBeTrue();
});

it('pre-fills new giveaway drafts with the guild default channel', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create(['default_channel_id' => '111']);
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->assertSet('channelId', '111');
});

it('denies settings access to a non-admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(GuildSettings::class, ['guild' => $guild])
        ->assertForbidden();
});

it('allows a scoped admin granted the settings section', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->granted(['settings'])->create();

    Livewire::actingAs($user)
        ->test(GuildSettings::class, ['guild' => $guild])
        ->assertOk();
});

it('denies a scoped admin not granted the settings section', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->granted(['giveaways'])->create();

    Livewire::actingAs($user)
        ->test(GuildSettings::class, ['guild' => $guild])
        ->assertForbidden();
});
