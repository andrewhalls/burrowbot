<?php

declare(strict_types=1);

use App\Models\DiscordRole;
use App\Models\Guild;

it('syncs roles for a guild', function () {
    $guild = Guild::factory()->create();

    $this->withHeaders(botAuthHeader())
        ->putJson("/internal/guilds/{$guild->discord_guild_id}/roles", [
            'roles' => [
                ['discord_role_id' => '111', 'name' => 'Moderator'],
                ['discord_role_id' => '222', 'name' => 'Raider'],
            ],
        ])
        ->assertStatus(200);

    expect(DiscordRole::query()->where('guild_id', $guild->id)->count())->toBe(2);
});

it('accepts an empty roles list', function () {
    $guild = Guild::factory()->create();
    DiscordRole::factory()->for($guild)->create();

    $this->withHeaders(botAuthHeader())
        ->putJson("/internal/guilds/{$guild->discord_guild_id}/roles", ['roles' => []])
        ->assertStatus(200);

    expect(DiscordRole::query()->where('guild_id', $guild->id)->count())->toBe(0);
});

it('validates required role fields', function () {
    $guild = Guild::factory()->create();

    $this->withHeaders(botAuthHeader())
        ->putJson("/internal/guilds/{$guild->discord_guild_id}/roles", [
            'roles' => [['name' => 'missing-id']],
        ])
        ->assertStatus(422);
});

it('rejects role sync requests without a valid bot token', function () {
    $guild = Guild::factory()->create();

    $this->putJson("/internal/guilds/{$guild->discord_guild_id}/roles", ['roles' => []])
        ->assertStatus(401);
});
