<?php

declare(strict_types=1);

use App\Actions\GuildAdmins\GrantGuildAdminSectionsAction;
use App\Models\DiscordMember;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

it('grants a member scoped access to the given sections', function () {
    $guild = Guild::factory()->create();
    $member = DiscordMember::factory()->for($guild)->create();

    $admin = (new GrantGuildAdminSectionsAction)->execute($guild, $member, ['giveaways', 'broadcasts']);

    expect($admin->source)->toBe(GuildAdmin::SOURCE_GRANTED)
        ->and($admin->sections)->toBe(['giveaways', 'broadcasts'])
        ->and($admin->discord_user_id)->toBe($member->discord_user_id);
});

it('resolves user_id immediately when the invitee already has a Burrow login', function () {
    $guild = Guild::factory()->create();
    $member = DiscordMember::factory()->for($guild)->create(['discord_user_id' => '555']);
    $user = User::factory()->create(['discord_user_id' => '555']);

    $admin = (new GrantGuildAdminSectionsAction)->execute($guild, $member, ['giveaways']);

    expect($admin->user_id)->toBe($user->id);
});

it('leaves user_id null when the invitee has never logged into Burrow', function () {
    $guild = Guild::factory()->create();
    $member = DiscordMember::factory()->for($guild)->create();

    $admin = (new GrantGuildAdminSectionsAction)->execute($guild, $member, ['giveaways']);

    expect($admin->user_id)->toBeNull();
});

it('replaces sections rather than stacking when re-inviting the same member', function () {
    $guild = Guild::factory()->create();
    $member = DiscordMember::factory()->for($guild)->create();
    (new GrantGuildAdminSectionsAction)->execute($guild, $member, ['giveaways']);

    (new GrantGuildAdminSectionsAction)->execute($guild, $member, ['broadcasts', 'events']);

    expect(GuildAdmin::query()->where('guild_id', $guild->id)->where('discord_user_id', $member->discord_user_id)->count())->toBe(1);
    $admin = GuildAdmin::query()->where('guild_id', $guild->id)->where('discord_user_id', $member->discord_user_id)->sole();
    expect($admin->sections)->toBe(['broadcasts', 'events']);
});
