<?php

declare(strict_types=1);

use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

it('allows a guild admin to manage role sets in their guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $roleSet = EventRoleSet::factory()->for($guild)->create();

    expect($user->can('manage', $roleSet))->toBeTrue();
});

it('denies managing a role set belonging to a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->create();

    expect($user->can('manage', $roleSet))->toBeFalse();
});
