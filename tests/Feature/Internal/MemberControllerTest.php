<?php

declare(strict_types=1);

use App\Models\DiscordMember;
use App\Models\Guild;

it('creates a member record on first observed interaction', function () {
    $guild = Guild::factory()->create();

    $this->withHeaders(botAuthHeader())
        ->putJson("/internal/guilds/{$guild->discord_guild_id}/members/12345", [
            'username' => 'newbie',
            'avatar_url' => 'https://cdn.discordapp.com/avatars/12345/a.png',
        ])
        ->assertStatus(200)
        ->assertJsonPath('username', 'newbie');

    expect(DiscordMember::query()
        ->where('guild_id', $guild->id)
        ->where('discord_user_id', '12345')
        ->exists())->toBeTrue();
});

it('updates the stored username when it changes on Discord', function () {
    $guild = Guild::factory()->create();
    $member = DiscordMember::factory()->for($guild)->create([
        'discord_user_id' => '12345',
        'username' => 'old-name',
    ]);

    $this->withHeaders(botAuthHeader())
        ->putJson("/internal/guilds/{$guild->discord_guild_id}/members/12345", [
            'username' => 'new-name',
        ])
        ->assertStatus(200);

    expect($member->fresh()->username)->toBe('new-name')
        ->and(DiscordMember::query()->where('guild_id', $guild->id)->count())->toBe(1);
});

it('records the display name when provided', function () {
    $guild = Guild::factory()->create();

    $this->withHeaders(botAuthHeader())
        ->putJson("/internal/guilds/{$guild->discord_guild_id}/members/12345", [
            'username' => 'newbie',
            'display_name' => 'The Newbie',
        ])
        ->assertStatus(200)
        ->assertJsonPath('display_name', 'The Newbie');

    $member = DiscordMember::query()->where('guild_id', $guild->id)->where('discord_user_id', '12345')->sole();
    expect($member->display_name)->toBe('The Newbie');
});

it('leaves the display name null when not provided', function () {
    $guild = Guild::factory()->create();

    $this->withHeaders(botAuthHeader())
        ->putJson("/internal/guilds/{$guild->discord_guild_id}/members/12345", ['username' => 'newbie'])
        ->assertStatus(200);

    $member = DiscordMember::query()->where('guild_id', $guild->id)->where('discord_user_id', '12345')->sole();
    expect($member->display_name)->toBeNull();
});

it('rejects member sync requests without a valid bot token', function () {
    $guild = Guild::factory()->create();

    $this->putJson("/internal/guilds/{$guild->discord_guild_id}/members/12345", ['username' => 'x'])
        ->assertStatus(401);
});
