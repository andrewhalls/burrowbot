<?php

declare(strict_types=1);

use App\Actions\Discord\SyncGuildRolesAction;
use App\Models\DiscordRole;
use App\Models\Guild;

function syncRolesAction(): SyncGuildRolesAction
{
    return app(SyncGuildRolesAction::class);
}

it('creates roles from the given list', function () {
    $guild = Guild::factory()->create();

    syncRolesAction()->execute($guild, [
        ['discord_role_id' => '111', 'name' => 'Moderator'],
        ['discord_role_id' => '222', 'name' => 'Raider'],
    ]);

    expect(DiscordRole::query()->where('guild_id', $guild->id)->count())->toBe(2)
        ->and(DiscordRole::query()->where('discord_role_id', '111')->value('name'))->toBe('Moderator');
});

it('updates a role name when it changes', function () {
    $guild = Guild::factory()->create();
    $role = DiscordRole::factory()->for($guild)->create(['discord_role_id' => '111', 'name' => 'old-name']);

    syncRolesAction()->execute($guild, [
        ['discord_role_id' => '111', 'name' => 'new-name'],
    ]);

    expect($role->fresh()->name)->toBe('new-name')
        ->and(DiscordRole::query()->where('guild_id', $guild->id)->count())->toBe(1);
});

it('deletes a role no longer present in the given list', function () {
    $guild = Guild::factory()->create();
    DiscordRole::factory()->for($guild)->create(['discord_role_id' => '111']);
    DiscordRole::factory()->for($guild)->create(['discord_role_id' => '222']);

    syncRolesAction()->execute($guild, [
        ['discord_role_id' => '111', 'name' => 'still-here'],
    ]);

    expect(DiscordRole::query()->where('guild_id', $guild->id)->pluck('discord_role_id')->all())->toBe(['111']);
});

it('deletes every role when given an empty list', function () {
    $guild = Guild::factory()->create();
    DiscordRole::factory()->for($guild)->create();

    syncRolesAction()->execute($guild, []);

    expect(DiscordRole::query()->where('guild_id', $guild->id)->count())->toBe(0);
});

it('never touches another guild roles', function () {
    $guildA = Guild::factory()->create();
    $guildB = Guild::factory()->create();
    DiscordRole::factory()->for($guildB)->create(['discord_role_id' => '999']);

    syncRolesAction()->execute($guildA, [['discord_role_id' => '111', 'name' => 'Moderator']]);

    expect(DiscordRole::query()->where('guild_id', $guildB->id)->where('discord_role_id', '999')->exists())->toBeTrue();
});
