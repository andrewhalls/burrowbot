<?php

declare(strict_types=1);

use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

it('allows a guild admin to view their guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    expect($user->can('view', $guild))->toBeTrue();
});

it('denies a user with no admin role on the guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    expect($user->can('view', $guild))->toBeFalse();
});

it('denies access to a guild the user administers a different guild in', function () {
    $user = User::factory()->create();
    $guildA = Guild::factory()->create();
    $guildB = Guild::factory()->create();
    GuildAdmin::factory()->for($guildA)->for($user)->create();

    expect($user->can('view', $guildA))->toBeTrue()
        ->and($user->can('view', $guildB))->toBeFalse();
});
