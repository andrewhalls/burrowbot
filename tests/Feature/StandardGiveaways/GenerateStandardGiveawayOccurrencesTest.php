<?php

declare(strict_types=1);

use App\Models\CollectionTheme;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use App\Models\StandardGiveawayPrizeItem;

it('generates occurrences for an active weekly recurring giveaway, snapshotting prize items', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $item = $theme->items->first();
    $giveaway = StandardGiveaway::factory()->for($theme->guild)->recurring(
        'FREQ=WEEKLY;BYDAY=FR',
        now()->next('Friday')->setTime(18, 0),
        'UTC',
    )->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway, 'standardGiveaway')->create(['collection_theme_item_id' => $item->id]);

    $this->artisan('standard-giveaways:generate-occurrences')->assertSuccessful();

    expect($giveaway->occurrences()->count())->toBeGreaterThan(0);

    $first = $giveaway->occurrences()->orderBy('scheduled_post_at')->first();
    expect($first->title)->toBe($giveaway->title)
        ->and($first->prize_item_ids)->toBe([$item->id])
        ->and($first->status)->toBe(StandardGiveawayOccurrence::STATUS_SCHEDULED);
});

it('does not generate duplicate occurrences on a second run', function () {
    $giveaway = StandardGiveaway::factory()->recurring(
        'FREQ=WEEKLY;BYDAY=FR',
        now()->next('Friday')->setTime(18, 0),
        'UTC',
    )->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway, 'standardGiveaway')->create();

    $this->artisan('standard-giveaways:generate-occurrences');
    $countAfterFirstRun = $giveaway->occurrences()->count();

    $this->artisan('standard-giveaways:generate-occurrences');
    $countAfterSecondRun = $giveaway->occurrences()->count();

    expect($countAfterSecondRun)->toBe($countAfterFirstRun);
});

it('does not generate occurrences for a one-off giveaway', function () {
    $giveaway = StandardGiveaway::factory()->create(); // recurrence_rule null

    $this->artisan('standard-giveaways:generate-occurrences');

    expect($giveaway->occurrences()->count())->toBe(0);
});

it('does not generate occurrences for a paused or cancelled giveaway', function () {
    $paused = StandardGiveaway::factory()->paused()->recurring('FREQ=DAILY', now()->addDay(), 'UTC')->create();
    $cancelled = StandardGiveaway::factory()->cancelled()->recurring('FREQ=DAILY', now()->addDay(), 'UTC')->create();

    $this->artisan('standard-giveaways:generate-occurrences');

    expect($paused->occurrences()->count())->toBe(0)
        ->and($cancelled->occurrences()->count())->toBe(0);
});
