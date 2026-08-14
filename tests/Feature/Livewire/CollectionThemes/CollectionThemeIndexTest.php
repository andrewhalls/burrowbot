<?php

declare(strict_types=1);

use App\Livewire\CollectionThemes\CollectionThemeIndex;
use App\Models\CollectionTheme;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('lists themed collections for the guild with their item count', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    CollectionTheme::factory()->for($guild)->withItems(3)->create(['name' => 'Retro Arcade']);

    Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->assertSee('Retro Arcade')
        ->assertSee('3 items');
});

it('shows only the selected theme\'s item-management UI in the detail panel', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $themeA = CollectionTheme::factory()->for($guild)->withItems(0)->create(['name' => 'Theme A']);
    $themeA->items()->create(['name' => 'Item A', 'sort_order' => 0]);
    $themeB = CollectionTheme::factory()->for($guild)->withItems(0)->create(['name' => 'Theme B']);
    $themeB->items()->create(['name' => 'Item B', 'sort_order' => 0]);

    $component = Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->assertSee('Select an item from the list')
        ->call('select', $themeA->id);

    $component->assertSee('Item A')->assertDontSee('Item B');

    $component->call('select', $themeB->id)
        ->assertSee('Item B')
        ->assertDontSee('Item A');
});

it('returns to the list-only view on deselect', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();

    Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->call('select', $theme->id)
        ->call('deselect')
        ->assertSee('Select an item from the list');
});

it('refuses to select a theme belonging to a different guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $otherGuild = Guild::factory()->create();
    $otherTheme = CollectionTheme::factory()->for($otherGuild)->withItems(1)->create();

    Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->call('select', $otherTheme->id)
        ->assertSee('Select an item from the list');
});

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(CollectionThemeIndex::class, ['guild' => $guild])
        ->assertForbidden();
});
