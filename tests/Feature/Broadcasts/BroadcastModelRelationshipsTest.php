<?php

declare(strict_types=1);

use App\Models\Broadcast;
use App\Models\BroadcastOccurrence;
use App\Models\Guild;
use Illuminate\Database\QueryException;

it('relates a guild to its broadcasts', function () {
    $guild = Guild::factory()->create();
    Broadcast::factory()->for($guild)->create();

    expect($guild->broadcasts)->toHaveCount(1);
});

it('relates a broadcast to its occurrences', function () {
    $broadcast = Broadcast::factory()->create();
    BroadcastOccurrence::factory()->for($broadcast)->create();
    BroadcastOccurrence::factory()->for($broadcast)->create(['scheduled_post_at' => now()->addWeek()]);

    expect($broadcast->occurrences)->toHaveCount(2);
});

it('enforces one occurrence per broadcast and scheduled_post_at', function () {
    $broadcast = Broadcast::factory()->create();
    $scheduledPostAt = now()->addWeek();

    BroadcastOccurrence::factory()->for($broadcast)->create(['scheduled_post_at' => $scheduledPostAt]);

    expect(fn () => BroadcastOccurrence::factory()->for($broadcast)->create(['scheduled_post_at' => $scheduledPostAt]))
        ->toThrow(QueryException::class);
});

it('reports a broadcast as recurring only when it has a recurrence rule', function () {
    $oneOff = Broadcast::factory()->create();
    $recurring = Broadcast::factory()->recurring('FREQ=WEEKLY;BYDAY=FR')->create();

    expect($oneOff->isRecurring())->toBeFalse()
        ->and($recurring->isRecurring())->toBeTrue();
});

it('reports a broadcast as deletable until one of its occurrences has posted', function () {
    $broadcast = Broadcast::factory()->create();

    expect($broadcast->isDeletable())->toBeTrue();

    BroadcastOccurrence::factory()->for($broadcast)->posted()->create();

    expect($broadcast->fresh()->isDeletable())->toBeFalse();
});
