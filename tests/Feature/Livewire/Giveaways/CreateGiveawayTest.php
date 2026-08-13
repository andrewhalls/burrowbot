<?php

declare(strict_types=1);

use App\Livewire\Giveaways\CreateGiveaway;
use App\Models\CollectionTheme;
use App\Models\Giveaway;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('creates a giveaway with valid input', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 15)
        ->call('save')
        ->assertDispatched('giveaway-created')
        ->assertHasNoErrors();

    expect(Giveaway::query()->where('guild_id', $guild->id)->count())->toBe(1);
});

it('rejects a non-positive duration', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 0)
        ->call('save')
        ->assertHasErrors('durationMinutes');

    expect(Giveaway::query()->count())->toBe(0);
});

it('rejects a collection theme belonging to a different guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $otherGuildTheme = CollectionTheme::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $otherGuildTheme->id)
        ->set('durationMinutes', 15)
        ->call('save')
        ->assertHasErrors('collectionThemeId');
});

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->assertForbidden();
});
