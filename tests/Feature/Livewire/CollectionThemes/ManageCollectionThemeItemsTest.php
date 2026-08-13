<?php

declare(strict_types=1);

use App\Livewire\CollectionThemes\ManageCollectionThemeItems;
use App\Models\CollectionTheme;
use App\Models\Giveaway;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

it('adds an item through the component', function () {
    $user = User::factory()->create();
    $theme = CollectionTheme::factory()->withItems(1)->create();
    GuildAdmin::factory()->for($theme->guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(ManageCollectionThemeItems::class, ['theme' => $theme])
        ->set('newItemName', 'Fresh Item')
        ->call('addItem')
        ->assertHasNoErrors();

    expect($theme->items()->where('name', 'Fresh Item')->exists())->toBeTrue();
});

it('shows the theme as locked and refuses to add an item while a giveaway is active', function () {
    $user = User::factory()->create();
    $theme = CollectionTheme::factory()->withItems(1)->create();
    GuildAdmin::factory()->for($theme->guild)->for($user)->create();
    Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    Livewire::actingAs($user)
        ->test(ManageCollectionThemeItems::class, ['theme' => $theme])
        ->assertSee('Locked while an active giveaway')
        ->set('newItemName', 'Fresh Item')
        ->call('addItem')
        ->assertHasErrors('newItemName');

    expect($theme->items()->count())->toBe(1);
});
