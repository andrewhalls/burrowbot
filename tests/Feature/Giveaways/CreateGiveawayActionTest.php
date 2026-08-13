<?php

declare(strict_types=1);

use App\Actions\Giveaways\CreateGiveawayAction;
use App\Models\CollectionTheme;
use App\Models\Giveaway;
use App\Models\Guild;

it('creates a giveaway in draft status', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    $giveaway = (new CreateGiveawayAction)->execute($guild, $theme, '999888777', 30);

    expect($giveaway->status)->toBe(Giveaway::STATUS_DRAFT)
        ->and($giveaway->guild_id)->toBe($guild->id)
        ->and($giveaway->collection_theme_id)->toBe($theme->id)
        ->and($giveaway->duration_minutes)->toBe(30)
        ->and($giveaway->starts_at)->toBeNull()
        ->and($giveaway->ends_at)->toBeNull();
});
