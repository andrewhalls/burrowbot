<?php

declare(strict_types=1);

use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

function userAdministering(Guild ...$guilds): User
{
    $user = User::factory()->create();
    foreach ($guilds as $guild) {
        GuildAdmin::factory()->for($guild)->for($user)->create();
    }

    return $user;
}

it('lists only guilds the user administers', function () {
    $administered = Guild::factory()->create(['name' => 'Mine']);
    $other = Guild::factory()->create(['name' => 'Not Mine']);
    $user = userAdministering($administered);

    $response = $this->actingAs($user)->get(route('guilds.events.index', $administered));

    $response->assertOk()->assertSee('Mine')->assertDontSee('Not Mine');
});

it('targets the same route for a guild when the current route only needs a guild parameter', function () {
    $guildA = Guild::factory()->create();
    $guildB = Guild::factory()->create();
    $user = userAdministering($guildA, $guildB);

    $response = $this->actingAs($user)->get(route('guilds.events.index', $guildA));

    $response->assertOk()->assertSee(route('guilds.events.index', $guildB), false);
});

it('targets guild settings when the current route needs more than a guild parameter', function () {
    $guildA = Guild::factory()->create();
    $guildB = Guild::factory()->create();
    $user = userAdministering($guildA, $guildB);

    $occurrence = \App\Models\EventOccurrence::factory()->create([
        'event_id' => \App\Models\Event::factory()->for($guildA)->create()->id,
    ]);

    $response = $this->actingAs($user)->get(route('guilds.event-occurrences.show', [$guildA, $occurrence]));

    $response->assertOk()->assertSee(route('guilds.settings', $guildB), false);
});
