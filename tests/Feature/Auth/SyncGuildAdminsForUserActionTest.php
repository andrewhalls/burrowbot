<?php

declare(strict_types=1);

use App\Actions\Auth\SyncGuildAdminsForUserAction;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

it('grants guild_admins for guilds the user currently administers', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create(['discord_guild_id' => '111']);

    (new SyncGuildAdminsForUserAction)->execute($user, ['111' => true]);

    expect($user->isAdminOfGuild($guild))->toBeTrue();
});

it('ignores guilds where the user lacks admin permissions', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create(['discord_guild_id' => '111']);

    (new SyncGuildAdminsForUserAction)->execute($user, ['111' => false]);

    expect($user->isAdminOfGuild($guild))->toBeFalse();
});

it('ignores discord guilds burrow does not know about', function () {
    $user = User::factory()->create();

    (new SyncGuildAdminsForUserAction)->execute($user, ['999-unregistered' => true]);

    expect(GuildAdmin::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('revokes a guild_admins row when Discord no longer reports the role', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create(['discord_guild_id' => '111']);
    GuildAdmin::factory()->for($guild)->for($user)->create();

    expect($user->isAdminOfGuild($guild))->toBeTrue();

    (new SyncGuildAdminsForUserAction)->execute($user, ['111' => false]);

    expect($user->fresh()->isAdminOfGuild($guild))->toBeFalse();
});

it('does not touch other users guild_admins rows', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $guild = Guild::factory()->create(['discord_guild_id' => '111']);
    GuildAdmin::factory()->for($guild)->for($otherUser)->create();

    (new SyncGuildAdminsForUserAction)->execute($user, []);

    expect($otherUser->isAdminOfGuild($guild))->toBeTrue();
});
