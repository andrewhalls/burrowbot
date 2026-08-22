<?php

declare(strict_types=1);

use App\Models\CollectionTheme;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

it('allows a guild admin to manage themes in their guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    expect($user->can('manage', $theme))->toBeTrue();
});

it('denies managing a theme belonging to a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    expect($user->can('manage', $theme))->toBeFalse();
});

it('allows a scoped admin granted the themes section', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->granted(['themes'])->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    expect($user->can('manage', $theme))->toBeTrue();
});

it('denies a scoped admin not granted the themes section', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->granted(['giveaways'])->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    expect($user->can('manage', $theme))->toBeFalse();
});
