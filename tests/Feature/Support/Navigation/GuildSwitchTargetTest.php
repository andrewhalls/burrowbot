<?php

declare(strict_types=1);

use App\Models\Guild;
use App\Support\Navigation\GuildSwitchTarget;
use Illuminate\Support\Facades\Route;

function namedRoute(string $name): \Illuminate\Routing\Route
{
    return Route::getRoutes()->getByName($name);
}

it('keeps the same route name when the route only needs a guild parameter', function () {
    $target = Guild::factory()->create();

    $url = GuildSwitchTarget::resolve(namedRoute('guilds.events.index'), $target);

    expect($url)->toBe(route('guilds.events.index', $target));
});

it('falls back to guild settings when the route needs more than a guild parameter', function () {
    $target = Guild::factory()->create();

    $url = GuildSwitchTarget::resolve(namedRoute('guilds.event-occurrences.show'), $target);

    expect($url)->toBe(route('guilds.settings', $target));
});

it('falls back to guild settings when there is no current route', function () {
    $target = Guild::factory()->create();

    $url = GuildSwitchTarget::resolve(null, $target);

    expect($url)->toBe(route('guilds.settings', $target));
});

it('falls back to guild settings for a route outside the guilds. naming convention', function () {
    $target = Guild::factory()->create();

    $url = GuildSwitchTarget::resolve(namedRoute('dashboard'), $target);

    expect($url)->toBe(route('guilds.settings', $target));
});
