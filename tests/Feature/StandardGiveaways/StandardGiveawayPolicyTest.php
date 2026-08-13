<?php

declare(strict_types=1);

use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\StandardGiveaway;
use App\Models\User;

it('allows a guild admin to manage standard giveaways in their guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();

    expect($user->can('manage', $giveaway))->toBeTrue();
});

it('denies managing a standard giveaway belonging to a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();

    expect($user->can('manage', $giveaway))->toBeFalse();
});
