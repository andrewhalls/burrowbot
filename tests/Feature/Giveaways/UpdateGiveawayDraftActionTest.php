<?php

declare(strict_types=1);

use App\Actions\Giveaways\UpdateGiveawayDraftAction;
use App\Models\CollectionTheme;
use App\Models\Giveaway;

it('allows editing a draft giveaway', function () {
    $giveaway = Giveaway::factory()->create(['duration_minutes' => 10]);

    $updated = (new UpdateGiveawayDraftAction)->execute($giveaway, ['duration_minutes' => 20]);

    expect($updated->duration_minutes)->toBe(20);
});

it('refuses to edit an active giveaway', function () {
    $giveaway = Giveaway::factory()->active()->create(['duration_minutes' => 10]);

    expect(fn () => (new UpdateGiveawayDraftAction)->execute($giveaway, ['duration_minutes' => 20]))
        ->toThrow(InvalidArgumentException::class);

    expect($giveaway->fresh()->duration_minutes)->toBe(10);
});

it('refuses to edit a closed giveaway', function () {
    $giveaway = Giveaway::factory()->closed()->create();
    $newTheme = CollectionTheme::factory()->create();

    expect(fn () => (new UpdateGiveawayDraftAction)->execute($giveaway, ['collection_theme_id' => $newTheme->id]))
        ->toThrow(InvalidArgumentException::class);
});
