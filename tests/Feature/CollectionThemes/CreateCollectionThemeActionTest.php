<?php

declare(strict_types=1);

use App\Actions\CollectionThemes\CreateCollectionThemeAction;
use App\Models\CollectionTheme;
use App\Models\Guild;

it('creates a theme with its items in order', function () {
    $guild = Guild::factory()->create();

    $theme = (new CreateCollectionThemeAction)->execute($guild, 'Retro Arcade', ['Joystick', 'Cartridge', 'Coin']);

    expect($theme)->toBeInstanceOf(CollectionTheme::class)
        ->and($theme->guild_id)->toBe($guild->id)
        ->and($theme->items->pluck('name')->all())->toBe(['Joystick', 'Cartridge', 'Coin']);
});

it('discards blank item rows before validating', function () {
    $guild = Guild::factory()->create();

    $theme = (new CreateCollectionThemeAction)->execute($guild, 'Retro Arcade', ['Joystick', '', '  ', 'Coin']);

    expect($theme->items)->toHaveCount(2);
});

it('rejects a theme with zero non-blank items and creates nothing', function () {
    $guild = Guild::factory()->create();

    expect(fn () => (new CreateCollectionThemeAction)->execute($guild, 'Empty Theme', ['', '  ']))
        ->toThrow(InvalidArgumentException::class);

    expect(CollectionTheme::query()->count())->toBe(0);
});
