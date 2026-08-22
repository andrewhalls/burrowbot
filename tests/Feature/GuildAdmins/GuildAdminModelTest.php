<?php

declare(strict_types=1);

use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use App\Support\GuildAdmins\GuildAdminSection;

it('reports a discord-synced admin as having every section', function () {
    $admin = GuildAdmin::factory()->discordSynced()->create();

    expect($admin->hasSection(GuildAdminSection::GIVEAWAYS))->toBeTrue()
        ->and($admin->hasSection(GuildAdminSection::SETTINGS))->toBeTrue()
        ->and($admin->hasSection(GuildAdminSection::BROADCASTS))->toBeTrue();
});

it('reports a granted admin as having only their granted sections', function () {
    $admin = GuildAdmin::factory()->granted([GuildAdminSection::GIVEAWAYS])->create();

    expect($admin->hasSection(GuildAdminSection::GIVEAWAYS))->toBeTrue()
        ->and($admin->hasSection(GuildAdminSection::BROADCASTS))->toBeFalse();
});

it('reports a pending grant (no user_id yet) as having its granted sections', function () {
    $admin = GuildAdmin::factory()->pending('999888777', [GuildAdminSection::BROADCASTS])->create();

    expect($admin->user_id)->toBeNull()
        ->and($admin->hasSection(GuildAdminSection::BROADCASTS))->toBeTrue()
        ->and($admin->hasSection(GuildAdminSection::EVENTS))->toBeFalse();
});

it('resolves a user\'s section access across a discord-synced admin row', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->discordSynced()->create();

    expect($user->hasGuildAdminSection($guild, GuildAdminSection::SETTINGS))->toBeTrue();
});

it('resolves a user\'s section access across a granted admin row', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->granted([GuildAdminSection::GIVEAWAYS])->create();

    expect($user->hasGuildAdminSection($guild, GuildAdminSection::GIVEAWAYS))->toBeTrue()
        ->and($user->hasGuildAdminSection($guild, GuildAdminSection::EVENTS))->toBeFalse();
});

it('denies section access for a user with no guild_admins row at all', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    expect($user->hasGuildAdminSection($guild, GuildAdminSection::GIVEAWAYS))->toBeFalse();
});
