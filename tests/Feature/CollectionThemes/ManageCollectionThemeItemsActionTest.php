<?php

declare(strict_types=1);

use App\Actions\CollectionThemes\ManageCollectionThemeItemsAction;
use App\Models\CollectionTheme;
use App\Models\CollectionThemeItem;
use App\Models\Giveaway;

it('adds an item to an editable theme', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();

    $item = (new ManageCollectionThemeItemsAction)->addItem($theme, 'New Item');

    expect($item->name)->toBe('New Item')
        ->and($theme->items()->count())->toBe(2);
});

it('removes an item from an editable theme', function () {
    $theme = CollectionTheme::factory()->withItems(2)->create();
    $item = $theme->items->first();

    (new ManageCollectionThemeItemsAction)->removeItem($theme, $item);

    expect(CollectionThemeItem::query()->find($item->id))->toBeNull();
});

it('blocks adding an item while the theme backs an active giveaway', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    expect(fn () => (new ManageCollectionThemeItemsAction)->addItem($theme, 'New Item'))
        ->toThrow(InvalidArgumentException::class);

    expect($theme->items()->count())->toBe(1);
});

it('blocks removing an item while the theme backs an active giveaway', function () {
    $theme = CollectionTheme::factory()->withItems(2)->create();
    $item = $theme->items->first();
    Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    expect(fn () => (new ManageCollectionThemeItemsAction)->removeItem($theme, $item))
        ->toThrow(InvalidArgumentException::class);

    expect(CollectionThemeItem::query()->find($item->id))->not->toBeNull();
});

it('allows editing again once the giveaway using the theme is closed', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    Giveaway::factory()->for($theme, 'collectionTheme')->closed()->create();

    $item = (new ManageCollectionThemeItemsAction)->addItem($theme, 'New Item');

    expect($item->exists)->toBeTrue();
});
