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

it('leaves a granted admin row untouched when the user is a guild member but not a Discord admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create(['discord_guild_id' => '111']);
    GuildAdmin::factory()->for($guild)->for($user)->granted(['giveaways'])->create();

    (new SyncGuildAdminsForUserAction)->execute($user, ['111' => false]);

    $admin = GuildAdmin::query()->where('guild_id', $guild->id)->where('user_id', $user->id)->sole();
    expect($admin->source)->toBe(GuildAdmin::SOURCE_GRANTED)
        ->and($admin->sections)->toBe(['giveaways']);
});

it('revokes a granted admin row once the user is no longer a member of the guild at all', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create(['discord_guild_id' => '111']);
    GuildAdmin::factory()->for($guild)->for($user)->granted(['giveaways'])->create();

    (new SyncGuildAdminsForUserAction)->execute($user, []);

    expect(GuildAdmin::query()->where('guild_id', $guild->id)->where('user_id', $user->id)->exists())->toBeFalse();
});

it('upgrades a granted admin row to discord_sync when the user becomes a real Discord admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create(['discord_guild_id' => '111']);
    $admin = GuildAdmin::factory()->for($guild)->for($user)->granted(['giveaways'])->create();

    (new SyncGuildAdminsForUserAction)->execute($user, ['111' => true]);

    expect(GuildAdmin::query()->where('guild_id', $guild->id)->where('user_id', $user->id)->count())->toBe(1)
        ->and($admin->fresh()->source)->toBe(GuildAdmin::SOURCE_DISCORD_SYNC)
        ->and($admin->fresh()->sections)->toBeNull();
});

it('backfills user_id on a pending grant the first time that Discord user logs in', function () {
    $guild = Guild::factory()->create(['discord_guild_id' => '111']);
    $pending = GuildAdmin::factory()->for($guild)->pending('555', ['broadcasts'])->create();
    $user = User::factory()->create(['discord_user_id' => '555']);

    (new SyncGuildAdminsForUserAction)->execute($user, ['111' => false]);

    expect($pending->fresh()->user_id)->toBe($user->id)
        ->and($user->hasGuildAdminSection($guild, 'broadcasts'))->toBeTrue();
});

it('does not backfill a pending grant belonging to a different discord user', function () {
    $guild = Guild::factory()->create(['discord_guild_id' => '111']);
    $pending = GuildAdmin::factory()->for($guild)->pending('999', ['broadcasts'])->create();
    $user = User::factory()->create(['discord_user_id' => '555']);

    (new SyncGuildAdminsForUserAction)->execute($user, ['111' => false]);

    expect($pending->fresh()->user_id)->toBeNull();
});
