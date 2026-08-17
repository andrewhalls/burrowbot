<?php

declare(strict_types=1);

use App\Actions\StandardGiveaways\UpdateStandardGiveawayOccurrenceAction;
use App\Models\CollectionThemeItem;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;

it('updates a scheduled occurrence\'s description and prize items', function () {
    $item = CollectionThemeItem::factory()->create();
    $occurrence = StandardGiveawayOccurrence::factory()->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'description' => 'Old description',
        'prize_item_ids' => [999],
    ]);

    (new UpdateStandardGiveawayOccurrenceAction)->execute($occurrence, [
        'description' => 'New description',
        'prize_item_ids' => [$item->id],
    ]);

    expect($occurrence->fresh()->description)->toBe('New description')
        ->and($occurrence->fresh()->prize_item_ids)->toBe([$item->id]);
});

it('leaves the series template and other occurrences untouched', function () {
    $giveaway = StandardGiveaway::factory()->create(['description' => 'Series description']);
    $other = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'description' => 'Other week',
        'scheduled_post_at' => now()->addWeek(),
    ]);
    $occurrence = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'description' => 'Old description',
        'scheduled_post_at' => now()->addWeeks(2),
    ]);

    (new UpdateStandardGiveawayOccurrenceAction)->execute($occurrence, ['description' => 'This week only']);

    expect($giveaway->fresh()->description)->toBe('Series description')
        ->and($other->fresh()->description)->toBe('Other week');
});

it('rejects editing a posted occurrence', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create(['description' => 'Original']);

    expect(fn () => (new UpdateStandardGiveawayOccurrenceAction)->execute($occurrence, ['description' => 'Changed']))
        ->toThrow(InvalidArgumentException::class);

    expect($occurrence->fresh()->description)->toBe('Original');
});

it('rejects editing a closed occurrence', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->create([
        'status' => StandardGiveawayOccurrence::STATUS_CLOSED,
        'description' => 'Original',
    ]);

    expect(fn () => (new UpdateStandardGiveawayOccurrenceAction)->execute($occurrence, ['description' => 'Changed']))
        ->toThrow(InvalidArgumentException::class);

    expect($occurrence->fresh()->description)->toBe('Original');
});
