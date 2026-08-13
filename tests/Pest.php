<?php

use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/**
 * Configures the bot service token and returns the Authorization header
 * array to authenticate as the Discord bot process against /internal/*.
 */
function botAuthHeader(string $token = 'test-bot-service-token'): array
{
    Config::set('discord.service_token', $token);

    return ['Authorization' => "Bearer {$token}"];
}

/**
 * Creates a user who administers the given guild.
 */
function actingEventStaffFor(Guild $guild): User
{
    $user = User::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    return $user;
}
