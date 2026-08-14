<?php

declare(strict_types=1);

use App\Livewire\CollectionThemes\ManageCollectionThemeItems;
use App\Models\CollectionTheme;
use App\Models\Giveaway;
use App\Models\GuildAdmin;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('adds an item with an image and shows it in the list', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $theme = CollectionTheme::factory()->withItems(0)->create();
    GuildAdmin::factory()->for($theme->guild)->for($user)->create();

    $component = Livewire::actingAs($user)
        ->test(ManageCollectionThemeItems::class, ['theme' => $theme])
        ->set('newItemName', 'Golden Ticket')
        ->set('newItemImage', UploadedFile::fake()->image('item.jpg'))
        ->call('addItem')
        ->assertHasNoErrors();

    $item = $theme->items()->where('name', 'Golden Ticket')->sole();
    expect($item->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($item->image_path);

    $component->assertSee($item->image_url, false);
});

it('adds an item without an image leaving it null', function () {
    $user = User::factory()->create();
    $theme = CollectionTheme::factory()->withItems(0)->create();
    GuildAdmin::factory()->for($theme->guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(ManageCollectionThemeItems::class, ['theme' => $theme])
        ->set('newItemName', 'Plain Item')
        ->call('addItem')
        ->assertHasNoErrors();

    $item = $theme->items()->where('name', 'Plain Item')->sole();
    expect($item->image_path)->toBeNull();
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
