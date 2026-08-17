<?php

declare(strict_types=1);

use App\Actions\StandardGiveaways\UpdateStandardGiveawayOccurrenceAction;
use App\Models\CollectionThemeItem;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Support\Facades\Storage;

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

it('sets an image on an occurrence with none', function () {
    Storage::fake('public');
    Storage::disk('public')->put('standard-giveaway-images/new.jpg', 'new-bytes');

    $occurrence = StandardGiveawayOccurrence::factory()->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'image_path' => null,
    ]);

    (new UpdateStandardGiveawayOccurrenceAction)->execute($occurrence, ['image_path' => 'standard-giveaway-images/new.jpg']);

    expect($occurrence->fresh()->image_path)->toBe('standard-giveaway-images/new.jpg');
});

it('deletes the old occurrence image once nothing else references it', function () {
    Storage::fake('public');
    Storage::disk('public')->put('standard-giveaway-images/old.jpg', 'old-bytes');
    Storage::disk('public')->put('standard-giveaway-images/new.jpg', 'new-bytes');

    $giveaway = StandardGiveaway::factory()->create(['image_path' => 'standard-giveaway-images/series.jpg']);
    $occurrence = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'image_path' => 'standard-giveaway-images/old.jpg',
    ]);

    (new UpdateStandardGiveawayOccurrenceAction)->execute($occurrence, ['image_path' => 'standard-giveaway-images/new.jpg']);

    Storage::disk('public')->assertMissing('standard-giveaway-images/old.jpg');
});

it('keeps the old occurrence image file while the series still references it', function () {
    Storage::fake('public');
    Storage::disk('public')->put('standard-giveaway-images/shared.jpg', 'bytes');
    Storage::disk('public')->put('standard-giveaway-images/new.jpg', 'new-bytes');

    $giveaway = StandardGiveaway::factory()->create(['image_path' => 'standard-giveaway-images/shared.jpg']);
    $occurrence = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'image_path' => 'standard-giveaway-images/shared.jpg',
    ]);

    (new UpdateStandardGiveawayOccurrenceAction)->execute($occurrence, ['image_path' => 'standard-giveaway-images/new.jpg']);

    Storage::disk('public')->assertExists('standard-giveaway-images/shared.jpg');
});

it('keeps the old occurrence image file while a sibling occurrence still references it', function () {
    Storage::fake('public');
    Storage::disk('public')->put('standard-giveaway-images/shared.jpg', 'bytes');
    Storage::disk('public')->put('standard-giveaway-images/new.jpg', 'new-bytes');

    $giveaway = StandardGiveaway::factory()->create();
    $sibling = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'image_path' => 'standard-giveaway-images/shared.jpg',
        'scheduled_post_at' => now()->addWeek(),
    ]);
    $occurrence = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'image_path' => 'standard-giveaway-images/shared.jpg',
        'scheduled_post_at' => now()->addWeeks(2),
    ]);

    (new UpdateStandardGiveawayOccurrenceAction)->execute($occurrence, ['image_path' => 'standard-giveaway-images/new.jpg']);

    Storage::disk('public')->assertExists('standard-giveaway-images/shared.jpg');
    expect($sibling->fresh()->image_path)->toBe('standard-giveaway-images/shared.jpg');
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
