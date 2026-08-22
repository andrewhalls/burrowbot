<?php

declare(strict_types=1);

use App\Models\Broadcast;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

it('allows a guild admin to manage broadcasts in their guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $broadcast = Broadcast::factory()->for($guild)->create();

    expect($user->can('manage', $broadcast))->toBeTrue();
});

it('denies managing a broadcast belonging to a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create();

    expect($user->can('manage', $broadcast))->toBeFalse();
});
