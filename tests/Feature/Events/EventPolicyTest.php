<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

it('allows a guild admin to manage events in their guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $event = Event::factory()->for($guild)->create();

    expect($user->can('manage', $event))->toBeTrue();
});

it('denies managing an event belonging to a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    $event = Event::factory()->for($guild)->create();

    expect($user->can('manage', $event))->toBeFalse();
});
