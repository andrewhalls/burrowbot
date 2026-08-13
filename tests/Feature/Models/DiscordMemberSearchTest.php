<?php

declare(strict_types=1);

use App\Models\DiscordMember;
use App\Models\Guild;

it('finds members by partial, case-insensitive username', function () {
    $guild = Guild::factory()->create();
    DiscordMember::factory()->for($guild)->create(['username' => 'ZeldaFan99']);
    DiscordMember::factory()->for($guild)->create(['username' => 'someone-else']);

    $results = DiscordMember::query()->where('guild_id', $guild->id)->search('zelda')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->username)->toBe('ZeldaFan99');
});

it('finds members by partial discord id', function () {
    $guild = Guild::factory()->create();
    DiscordMember::factory()->for($guild)->create(['discord_user_id' => '778899']);
    DiscordMember::factory()->for($guild)->create(['discord_user_id' => '111222']);

    $results = DiscordMember::query()->where('guild_id', $guild->id)->search('7788')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->discord_user_id)->toBe('778899');
});

it('does not leak matches from another guild', function () {
    $guildA = Guild::factory()->create();
    $guildB = Guild::factory()->create();
    DiscordMember::factory()->for($guildA)->create(['username' => 'sharedname']);
    DiscordMember::factory()->for($guildB)->create(['username' => 'sharedname']);

    $results = DiscordMember::query()->where('guild_id', $guildA->id)->search('sharedname')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->guild_id)->toBe($guildA->id);
});
