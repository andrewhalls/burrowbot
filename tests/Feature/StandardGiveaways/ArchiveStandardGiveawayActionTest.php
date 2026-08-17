<?php

declare(strict_types=1);

use App\Actions\StandardGiveaways\ArchiveStandardGiveawayAction;
use App\Actions\StandardGiveaways\UnarchiveStandardGiveawayAction;
use App\Models\StandardGiveaway;

it('archives an active giveaway, cancelling it and stamping archived_at', function () {
    $giveaway = StandardGiveaway::factory()->create(['status' => StandardGiveaway::STATUS_ACTIVE]);

    (new ArchiveStandardGiveawayAction)->execute($giveaway);

    expect($giveaway->fresh()->status)->toBe(StandardGiveaway::STATUS_CANCELLED)
        ->and($giveaway->fresh()->archived_at)->not->toBeNull();
});

it('archives a paused giveaway, cancelling it and stamping archived_at', function () {
    $giveaway = StandardGiveaway::factory()->paused()->create();

    (new ArchiveStandardGiveawayAction)->execute($giveaway);

    expect($giveaway->fresh()->status)->toBe(StandardGiveaway::STATUS_CANCELLED)
        ->and($giveaway->fresh()->archived_at)->not->toBeNull();
});

it('archives an already-cancelled giveaway without erroring, stamping archived_at', function () {
    $giveaway = StandardGiveaway::factory()->cancelled()->create();

    (new ArchiveStandardGiveawayAction)->execute($giveaway);

    expect($giveaway->fresh()->status)->toBe(StandardGiveaway::STATUS_CANCELLED)
        ->and($giveaway->fresh()->archived_at)->not->toBeNull();
});

it('unarchiving clears archived_at only, leaving status untouched', function () {
    $giveaway = StandardGiveaway::factory()->archived()->create();

    (new UnarchiveStandardGiveawayAction)->execute($giveaway);

    expect($giveaway->fresh()->archived_at)->toBeNull()
        ->and($giveaway->fresh()->status)->toBe(StandardGiveaway::STATUS_CANCELLED);
});
