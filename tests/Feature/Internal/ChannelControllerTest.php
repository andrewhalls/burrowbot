<?php

declare(strict_types=1);

use App\Models\DiscordChannel;
use App\Models\Guild;

it('syncs channels for a guild', function () {
    $guild = Guild::factory()->create();

    $this->withHeaders(botAuthHeader())
        ->putJson("/internal/guilds/{$guild->discord_guild_id}/channels", [
            'channels' => [
                ['discord_channel_id' => '111', 'name' => 'general'],
                ['discord_channel_id' => '222', 'name' => 'announcements'],
            ],
        ])
        ->assertStatus(200);

    expect(DiscordChannel::query()->where('guild_id', $guild->id)->count())->toBe(2);
});

it('accepts an empty channels list', function () {
    $guild = Guild::factory()->create();
    DiscordChannel::factory()->for($guild)->create();

    $this->withHeaders(botAuthHeader())
        ->putJson("/internal/guilds/{$guild->discord_guild_id}/channels", ['channels' => []])
        ->assertStatus(200);

    expect(DiscordChannel::query()->where('guild_id', $guild->id)->count())->toBe(0);
});

it('validates required channel fields', function () {
    $guild = Guild::factory()->create();

    $this->withHeaders(botAuthHeader())
        ->putJson("/internal/guilds/{$guild->discord_guild_id}/channels", [
            'channels' => [['name' => 'missing-id']],
        ])
        ->assertStatus(422);
});

it('rejects channel sync requests without a valid bot token', function () {
    $guild = Guild::factory()->create();

    $this->putJson("/internal/guilds/{$guild->discord_guild_id}/channels", ['channels' => []])
        ->assertStatus(401);
});
