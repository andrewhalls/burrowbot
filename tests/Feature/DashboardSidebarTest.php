<?php

declare(strict_types=1);

use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

it('shows all seven sections plus Admins to a full admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->discordSynced()->create();

    $response = $this->actingAs($user)->get(route('guilds.giveaways.index', $guild));

    $response->assertOk()
        ->assertSee('Settings', false)
        ->assertSee('Collection themes', false)
        ->assertSee('Event role sets', false)
        ->assertSee('Events', false)
        ->assertSee('Popup giveaways', false)
        ->assertSee('Standard giveaways', false)
        ->assertSee('Broadcasts', false)
        ->assertSee('Admins', false);
});

it('shows only granted sections, and no Admins link, to a scoped admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->granted(['giveaways', 'broadcasts'])->create();

    $response = $this->actingAs($user)->get(route('guilds.giveaways.index', $guild));

    $response->assertOk()
        ->assertSee('Popup giveaways', false)
        ->assertSee('Broadcasts', false)
        ->assertDontSee('Settings', false)
        ->assertDontSee('Collection themes', false)
        ->assertDontSee('Event role sets', false)
        ->assertDontSee('Standard giveaways', false)
        ->assertDontSee('Admins', false);
});

it('denies the scoped admin from reaching an ungranted section directly by URL', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->granted(['giveaways'])->create();

    $response = $this->actingAs($user)->get(route('guilds.broadcasts.index', $guild));

    $response->assertForbidden();
});
