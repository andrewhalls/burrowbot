<?php

declare(strict_types=1);

use App\Models\Broadcast;
use App\Models\BroadcastOccurrence;

it('generates occurrences for an active weekly recurring broadcast', function () {
    $broadcast = Broadcast::factory()->recurring(
        'FREQ=WEEKLY;BYDAY=WE',
        now()->next('Wednesday')->setTime(20, 0),
        'UTC',
    )->create();

    $this->artisan('broadcasts:generate-occurrences')->assertSuccessful();

    expect($broadcast->occurrences()->count())->toBeGreaterThan(0);

    $first = $broadcast->occurrences()->orderBy('scheduled_post_at')->first();
    expect($first->message_template)->toBe($broadcast->message_template)
        ->and($first->channel_id)->toBe($broadcast->channel_id)
        ->and($first->status)->toBe(BroadcastOccurrence::STATUS_SCHEDULED);
});

it('snapshots the broadcast\'s current message template into generated occurrences', function () {
    $broadcast = Broadcast::factory()->recurring(
        'FREQ=WEEKLY;BYDAY=WE',
        now()->next('Wednesday')->setTime(20, 0),
        'UTC',
    )->create(['message_template' => 'Original {{date}}']);

    $this->artisan('broadcasts:generate-occurrences')->assertSuccessful();

    $first = $broadcast->occurrences()->orderBy('scheduled_post_at')->first();
    expect($first->message_template)->toBe('Original {{date}}');
});

it('stores scheduled_post_at as a true UTC instant, not the recurrence timezone\'s wall-clock digits', function () {
    $localStart = now('Asia/Tokyo')->next('Monday')->setTime(18, 0);
    $broadcast = Broadcast::factory()->recurring('FREQ=WEEKLY;BYDAY=MO', $localStart, 'Asia/Tokyo')->create();

    $this->artisan('broadcasts:generate-occurrences')->assertSuccessful();

    $first = $broadcast->occurrences()->orderBy('scheduled_post_at')->first();

    expect($first->scheduled_post_at->clone()->utc()->format('H:i'))->toBe('09:00');
});

it('does not generate duplicate occurrences on a second run', function () {
    $broadcast = Broadcast::factory()->recurring(
        'FREQ=WEEKLY;BYDAY=WE',
        now()->next('Wednesday')->setTime(20, 0),
        'UTC',
    )->create();

    $this->artisan('broadcasts:generate-occurrences');
    $countAfterFirstRun = $broadcast->occurrences()->count();

    $this->artisan('broadcasts:generate-occurrences');
    $countAfterSecondRun = $broadcast->occurrences()->count();

    expect($countAfterSecondRun)->toBe($countAfterFirstRun);
});

it('does not generate occurrences for a one-off broadcast', function () {
    $broadcast = Broadcast::factory()->create(); // recurrence_rule null

    $this->artisan('broadcasts:generate-occurrences');

    expect($broadcast->occurrences()->count())->toBe(0);
});

it('does not generate occurrences for a paused or cancelled broadcast', function () {
    $paused = Broadcast::factory()->paused()->recurring('FREQ=DAILY', now()->addDay(), 'UTC')->create();
    $cancelled = Broadcast::factory()->cancelled()->recurring('FREQ=DAILY', now()->addDay(), 'UTC')->create();

    $this->artisan('broadcasts:generate-occurrences');

    expect($paused->occurrences()->count())->toBe(0)
        ->and($cancelled->occurrences()->count())->toBe(0);
});
