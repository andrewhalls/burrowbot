<?php

declare(strict_types=1);

use App\Actions\StandardGiveaways\DeleteStandardGiveawayAction;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;

it('deletes a series with no occurrences yet', function () {
    $giveaway = StandardGiveaway::factory()->create();

    (new DeleteStandardGiveawayAction)->execute($giveaway);

    expect(StandardGiveaway::query()->find($giveaway->id))->toBeNull();
});

it('deletes a series whose occurrences are all still scheduled, cascading the occurrences', function () {
    $giveaway = StandardGiveaway::factory()->create();
    $occurrence = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create(['status' => StandardGiveawayOccurrence::STATUS_SCHEDULED]);

    (new DeleteStandardGiveawayAction)->execute($giveaway);

    expect(StandardGiveaway::query()->find($giveaway->id))->toBeNull()
        ->and(StandardGiveawayOccurrence::query()->find($occurrence->id))->toBeNull();
});

it('rejects deleting a series with a posted occurrence', function () {
    $giveaway = StandardGiveaway::factory()->create();
    StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->posted()->create();

    expect(fn () => (new DeleteStandardGiveawayAction)->execute($giveaway))
        ->toThrow(InvalidArgumentException::class);

    expect(StandardGiveaway::query()->find($giveaway->id))->not->toBeNull();
});

it('rejects deleting a series with a closed occurrence', function () {
    $giveaway = StandardGiveaway::factory()->create();
    StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create(['status' => StandardGiveawayOccurrence::STATUS_CLOSED]);

    expect(fn () => (new DeleteStandardGiveawayAction)->execute($giveaway))
        ->toThrow(InvalidArgumentException::class);

    expect(StandardGiveaway::query()->find($giveaway->id))->not->toBeNull();
});
