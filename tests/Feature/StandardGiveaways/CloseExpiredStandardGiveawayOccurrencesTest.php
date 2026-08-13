<?php

declare(strict_types=1);

use App\Models\StandardGiveawayOccurrence;

it('closes occurrences whose end time has passed', function () {
    $expired = StandardGiveawayOccurrence::factory()->ended()->create();
    $stillOpen = StandardGiveawayOccurrence::factory()->posted()->create(['ends_at' => now()->addHour()]);

    $this->artisan('standard-giveaways:close-expired')->assertSuccessful();

    expect($expired->fresh()->status)->toBe(StandardGiveawayOccurrence::STATUS_CLOSED)
        ->and($stillOpen->fresh()->status)->toBe(StandardGiveawayOccurrence::STATUS_POSTED);
});

it('does not touch scheduled or already-closed occurrences', function () {
    $scheduled = StandardGiveawayOccurrence::factory()->create();
    $closed = StandardGiveawayOccurrence::factory()->closed()->create();

    $this->artisan('standard-giveaways:close-expired')->assertSuccessful();

    expect($scheduled->fresh()->status)->toBe(StandardGiveawayOccurrence::STATUS_SCHEDULED)
        ->and($closed->fresh()->status)->toBe(StandardGiveawayOccurrence::STATUS_CLOSED);
});
