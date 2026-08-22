<?php

declare(strict_types=1);

use App\Models\Giveaway;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

it('allows a guild admin to manage giveaways in their guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $giveaway = Giveaway::factory()->for($guild)->create();

    expect($user->can('manage', $giveaway))->toBeTrue();
});

it('denies managing a giveaway belonging to a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    $giveaway = Giveaway::factory()->for($guild)->create();

    expect($user->can('manage', $giveaway))->toBeFalse();
});

it('allows a scoped admin granted the giveaways section', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->granted(['giveaways'])->create();
    $giveaway = Giveaway::factory()->for($guild)->create();

    expect($user->can('manage', $giveaway))->toBeTrue();
});

it('denies a scoped admin not granted the giveaways section', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->granted(['events'])->create();
    $giveaway = Giveaway::factory()->for($guild)->create();

    expect($user->can('manage', $giveaway))->toBeFalse();
});
