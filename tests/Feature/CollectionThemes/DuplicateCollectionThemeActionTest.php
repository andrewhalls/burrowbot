<?php

declare(strict_types=1);

use App\Actions\CollectionThemes\DuplicateCollectionThemeAction;
use App\Models\CollectionTheme;

it('creates an independent copy of the theme\'s items', function () {
    $theme = CollectionTheme::factory()->withItems(0)->create(['name' => 'Retro Arcade']);
    $itemA = $theme->items()->create(['name' => 'Joystick', 'image_path' => 'theme-item-images/a.jpg', 'sort_order' => 0]);
    $itemB = $theme->items()->create(['name' => 'Cartridge', 'sort_order' => 1]);

    $duplicate = (new DuplicateCollectionThemeAction)->execute($theme);

    expect($duplicate->id)->not->toBe($theme->id)
        ->and($duplicate->guild_id)->toBe($theme->guild_id)
        ->and($duplicate->items)->toHaveCount(2)
        ->and($duplicate->items->pluck('name')->all())->toBe(['Joystick', 'Cartridge'])
        ->and($duplicate->items->firstWhere('name', 'Joystick')->image_path)->toBe('theme-item-images/a.jpg');

    // Independent rows, not shared - removing from the source leaves the duplicate untouched.
    $itemA->delete();
    expect($duplicate->items()->count())->toBe(2);
});

it('copies the theme\'s own image', function () {
    $theme = CollectionTheme::factory()->withItems(0)->withImage()->create();

    $duplicate = (new DuplicateCollectionThemeAction)->execute($theme);

    expect($duplicate->image_path)->toBe($theme->image_path);
});

it('derives the duplicate\'s name from the source', function () {
    $theme = CollectionTheme::factory()->withItems(0)->create(['name' => 'Retro Arcade']);

    $duplicate = (new DuplicateCollectionThemeAction)->execute($theme);

    expect($duplicate->name)->toBe('Retro Arcade (Copy)');
});

it('succeeds when duplicating a theme with zero items', function () {
    $theme = CollectionTheme::factory()->withItems(0)->create();

    $duplicate = (new DuplicateCollectionThemeAction)->execute($theme);

    expect($duplicate->items)->toHaveCount(0);
});
