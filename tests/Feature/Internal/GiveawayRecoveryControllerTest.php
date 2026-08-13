<?php

declare(strict_types=1);

use App\Models\Giveaway;
use App\Models\Guild;

it('returns only active giveaways with their guild discord id', function () {
    $guild = Guild::factory()->create();
    $active = Giveaway::factory()->for($guild)->active()->create();
    Giveaway::factory()->for($guild)->create(); // draft
    Giveaway::factory()->for($guild)->closed()->create();

    $this->withHeaders(botAuthHeader())
        ->getJson('/internal/giveaways/active')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $active->id)
        ->assertJsonPath('0.guild_discord_id', $guild->discord_guild_id)
        ->assertJsonPath('0.channel_id', $active->channel_id);
});

it('rejects recovery requests without a valid bot token', function () {
    $this->getJson('/internal/giveaways/active')->assertStatus(401);
});
