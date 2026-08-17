<?php

declare(strict_types=1);

use App\Actions\Giveaways\DeleteGiveawayDraftAction;
use App\Models\CollectionTheme;
use App\Models\Giveaway;

it('deletes a draft giveaway', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->create();

    (new DeleteGiveawayDraftAction)->execute($giveaway);

    expect(Giveaway::query()->find($giveaway->id))->toBeNull();
});

it('rejects deleting an active giveaway', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    expect(fn () => (new DeleteGiveawayDraftAction)->execute($giveaway))
        ->toThrow(InvalidArgumentException::class);

    expect(Giveaway::query()->find($giveaway->id))->not->toBeNull();
});

it('rejects deleting a closed giveaway', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->closed()->create();

    expect(fn () => (new DeleteGiveawayDraftAction)->execute($giveaway))
        ->toThrow(InvalidArgumentException::class);

    expect(Giveaway::query()->find($giveaway->id))->not->toBeNull();
});
