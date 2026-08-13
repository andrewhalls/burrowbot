<?php

declare(strict_types=1);

use App\Actions\StandardGiveaways\UpdateStandardGiveawayAction;
use App\Actions\StandardGiveaways\UpdateStandardGiveawayStatusAction;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;

it('updates giveaway fields without touching existing occurrences', function () {
    $giveaway = StandardGiveaway::factory()->create(['title' => 'Old Title']);
    $occurrence = StandardGiveawayOccurrence::factory()->create([
        'standard_giveaway_id' => $giveaway->id,
        'title' => 'Old Title',
    ]);

    (new UpdateStandardGiveawayAction)->execute($giveaway, ['title' => 'New Title']);

    expect($giveaway->fresh()->title)->toBe('New Title')
        ->and($occurrence->fresh()->title)->toBe('Old Title');
});

it('transitions giveaway status', function () {
    $giveaway = StandardGiveaway::factory()->create(['status' => StandardGiveaway::STATUS_ACTIVE]);

    (new UpdateStandardGiveawayStatusAction)->execute($giveaway, StandardGiveaway::STATUS_PAUSED);

    expect($giveaway->fresh()->status)->toBe(StandardGiveaway::STATUS_PAUSED);
});

it('rejects an invalid status', function () {
    $giveaway = StandardGiveaway::factory()->create();

    expect(fn () => (new UpdateStandardGiveawayStatusAction)->execute($giveaway, 'bogus'))
        ->toThrow(InvalidArgumentException::class);
});
