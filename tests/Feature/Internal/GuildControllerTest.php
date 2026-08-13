<?php

declare(strict_types=1);

use App\Models\Guild;

it('registers a new guild when the bot reports it has joined', function () {
    $this->withHeaders(botAuthHeader())
        ->postJson('/internal/guilds', [
            'discord_guild_id' => '111222333',
            'name' => 'Acme Server',
        ])
        ->assertStatus(201)
        ->assertJsonPath('discord_guild_id', '111222333')
        ->assertJsonPath('is_active', true);

    expect(Guild::query()->where('discord_guild_id', '111222333')->exists())->toBeTrue();
});

it('does not duplicate a guild the bot reports joining twice', function () {
    $headers = botAuthHeader();

    $this->withHeaders($headers)->postJson('/internal/guilds', [
        'discord_guild_id' => '111222333',
        'name' => 'Acme Server',
    ])->assertStatus(201);

    $this->withHeaders($headers)->postJson('/internal/guilds', [
        'discord_guild_id' => '111222333',
        'name' => 'Acme Server',
    ])->assertStatus(201);

    expect(Guild::query()->where('discord_guild_id', '111222333')->count())->toBe(1);
});

it('marks a guild inactive when the bot reports it was removed', function () {
    $guild = Guild::factory()->create(['discord_guild_id' => '999', 'is_active' => true]);

    $this->withHeaders(botAuthHeader())
        ->patchJson("/internal/guilds/{$guild->discord_guild_id}", ['is_active' => false])
        ->assertStatus(200)
        ->assertJsonPath('is_active', false);

    expect($guild->fresh()->is_active)->toBeFalse();
});

it('rejects guild sync requests without a valid bot token', function () {
    $this->postJson('/internal/guilds', [
        'discord_guild_id' => '111',
        'name' => 'Acme Server',
    ])->assertStatus(401);
});
