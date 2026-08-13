<?php

declare(strict_types=1);

use App\Models\Guild;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeSocialiteDiscordUser(string $discordId = '555', string $accessToken = 'discord-access-token'): SocialiteUser
{
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = $discordId;
    $socialiteUser->nickname = 'Staffer';
    $socialiteUser->name = 'Staffer Name';
    $socialiteUser->email = 'staffer@example.com';
    $socialiteUser->avatar = 'https://cdn.discordapp.com/avatars/555/abc.png';
    $socialiteUser->token = $accessToken;

    return $socialiteUser;
}

it('creates a user and logs them in on a successful Discord callback', function () {
    $guild = Guild::factory()->create(['discord_guild_id' => '999']);

    Socialite::shouldReceive('driver->user')->andReturn(fakeSocialiteDiscordUser());
    Http::fake([
        'discord.com/api/users/@me/guilds' => Http::response([
            ['id' => '999', 'permissions' => '32'], // MANAGE_GUILD bit
        ]),
    ]);

    $response = $this->get('/auth/discord/callback');

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $user = User::query()->where('discord_user_id', '555')->first();
    expect($user)->not->toBeNull()
        ->and($user->isAdminOfGuild($guild))->toBeTrue();
});

it('reuses the existing user on a repeat login instead of duplicating it', function () {
    $existing = User::factory()->create(['discord_user_id' => '555']);

    Socialite::shouldReceive('driver->user')->andReturn(fakeSocialiteDiscordUser());
    Http::fake(['discord.com/*' => Http::response([])]);

    $this->get('/auth/discord/callback');

    expect(User::query()->where('discord_user_id', '555')->count())->toBe(1)
        ->and(auth()->id())->toBe($existing->id);
});

it('redirects back to login without a session when Discord consent is denied', function () {
    $response = $this->get('/auth/discord/callback?error=access_denied');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
