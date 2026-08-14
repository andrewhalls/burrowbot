<?php

declare(strict_types=1);

use App\Actions\Discord\SyncGuildChannelsAction;
use App\Models\DiscordChannel;
use App\Models\Guild;

function syncChannelsAction(): SyncGuildChannelsAction
{
    return app(SyncGuildChannelsAction::class);
}

it('creates channels from the given list', function () {
    $guild = Guild::factory()->create();

    syncChannelsAction()->execute($guild, [
        ['discord_channel_id' => '111', 'name' => 'general'],
        ['discord_channel_id' => '222', 'name' => 'announcements'],
    ]);

    expect(DiscordChannel::query()->where('guild_id', $guild->id)->count())->toBe(2)
        ->and(DiscordChannel::query()->where('discord_channel_id', '111')->value('name'))->toBe('general');
});

it('updates a channel name when it changes', function () {
    $guild = Guild::factory()->create();
    $channel = DiscordChannel::factory()->for($guild)->create(['discord_channel_id' => '111', 'name' => 'old-name']);

    syncChannelsAction()->execute($guild, [
        ['discord_channel_id' => '111', 'name' => 'new-name'],
    ]);

    expect($channel->fresh()->name)->toBe('new-name')
        ->and(DiscordChannel::query()->where('guild_id', $guild->id)->count())->toBe(1);
});

it('deletes a channel no longer present in the given list', function () {
    $guild = Guild::factory()->create();
    DiscordChannel::factory()->for($guild)->create(['discord_channel_id' => '111']);
    DiscordChannel::factory()->for($guild)->create(['discord_channel_id' => '222']);

    syncChannelsAction()->execute($guild, [
        ['discord_channel_id' => '111', 'name' => 'still-here'],
    ]);

    expect(DiscordChannel::query()->where('guild_id', $guild->id)->pluck('discord_channel_id')->all())->toBe(['111']);
});

it('deletes every channel when given an empty list', function () {
    $guild = Guild::factory()->create();
    DiscordChannel::factory()->for($guild)->create();

    syncChannelsAction()->execute($guild, []);

    expect(DiscordChannel::query()->where('guild_id', $guild->id)->count())->toBe(0);
});

it('never touches another guild channels', function () {
    $guildA = Guild::factory()->create();
    $guildB = Guild::factory()->create();
    DiscordChannel::factory()->for($guildB)->create(['discord_channel_id' => '999']);

    syncChannelsAction()->execute($guildA, [['discord_channel_id' => '111', 'name' => 'general']]);

    expect(DiscordChannel::query()->where('guild_id', $guildB->id)->where('discord_channel_id', '999')->exists())->toBeTrue();
});
