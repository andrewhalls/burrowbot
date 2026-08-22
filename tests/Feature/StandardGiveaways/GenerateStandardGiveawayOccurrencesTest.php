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

it('snapshots the giveaway image into each generated occurrence', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $item = $theme->items->first();
    $giveaway = StandardGiveaway::factory()->for($theme->guild)->withImage('standard-giveaway-images/abc.jpg')->recurring(
        'FREQ=WEEKLY;BYDAY=FR',
        now()->next('Friday')->setTime(18, 0),
        'UTC',
    )->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway, 'standardGiveaway')->create(['collection_theme_item_id' => $item->id]);

    $this->artisan('standard-giveaways:generate-occurrences')->assertSuccessful();

    $first = $giveaway->occurrences()->orderBy('scheduled_post_at')->first();
    expect($first->image_path)->toBe('standard-giveaway-images/abc.jpg');
});

it('snapshots the giveaway banner image and claim/congrats fields into each generated occurrence', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $item = $theme->items->first();
    $giveaway = StandardGiveaway::factory()->for($theme->guild)
        ->withBannerImage('standard-giveaway-images/banner.jpg')
        ->withClaimDetails('https://discord.com/channels/1/2', 48, 'Congrats {winners}!')
        ->recurring('FREQ=WEEKLY;BYDAY=FR', now()->next('Friday')->setTime(18, 0), 'UTC')
        ->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway, 'standardGiveaway')->create(['collection_theme_item_id' => $item->id]);

    $this->artisan('standard-giveaways:generate-occurrences')->assertSuccessful();

    $first = $giveaway->occurrences()->orderBy('scheduled_post_at')->first();
    expect($first->banner_image_path)->toBe('standard-giveaway-images/banner.jpg')
        ->and($first->claim_link)->toBe('https://discord.com/channels/1/2')
        ->and($first->claim_deadline_hours)->toBe(48)
        ->and($first->congrats_message_template)->toBe('Congrats {winners}!');
});

it('snapshots the giveaway\'s per-winner message fields into each generated occurrence', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $item = $theme->items->first();
    $giveaway = StandardGiveaway::factory()->for($theme->guild)
        ->withPerWinnerMessage('987654', 'Congrats {winner}!')
        ->recurring('FREQ=WEEKLY;BYDAY=FR', now()->next('Friday')->setTime(18, 0), 'UTC')
        ->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway, 'standardGiveaway')->create(['collection_theme_item_id' => $item->id]);

    $this->artisan('standard-giveaways:generate-occurrences')->assertSuccessful();

    $first = $giveaway->occurrences()->orderBy('scheduled_post_at')->first();
    expect($first->per_winner_message_channel_id)->toBe('987654')
        ->and($first->per_winner_message_template)->toBe('Congrats {winner}!');
});

it('stores scheduled_post_at as a true UTC instant, not the recurrence timezone\'s wall-clock digits', function () {
    $localStart = now('Asia/Tokyo')->next('Friday')->setTime(18, 0);
    $giveaway = StandardGiveaway::factory()->recurring('FREQ=WEEKLY;BYDAY=FR', $localStart, 'Asia/Tokyo')->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway, 'standardGiveaway')->create();

    $this->artisan('standard-giveaways:generate-occurrences')->assertSuccessful();

    $first = $giveaway->occurrences()->orderBy('scheduled_post_at')->first();

    // 18:00 in Asia/Tokyo (UTC+9, no DST) is 09:00 UTC - if scheduled_post_at
    // were wrongly stored as the wall-clock "18:00" digits instead of the
    // converted UTC instant, this would read "18:00" instead.
    expect($first->scheduled_post_at->clone()->utc()->format('H:i'))->toBe('09:00');
});

it('generates several weeks of occurrences in advance for a weekly series (widened window)', function () {
    $giveaway = StandardGiveaway::factory()->recurring(
        'FREQ=WEEKLY;BYDAY=FR',
        now()->next('Friday')->setTime(18, 0),
        'UTC',
    )->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway, 'standardGiveaway')->create();

    $this->artisan('standard-giveaways:generate-occurrences');

    // 90-day window / weekly cadence is comfortably more than the ~4
    // occurrences a 30-day window would have produced.
    expect($giveaway->occurrences()->count())->toBeGreaterThan(6);
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
