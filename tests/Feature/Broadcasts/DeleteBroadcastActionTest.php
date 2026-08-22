<?php

declare(strict_types=1);

use App\Actions\Broadcasts\DeleteBroadcastAction;
use App\Models\Broadcast;
use App\Models\BroadcastOccurrence;

it('deletes a series with no occurrences yet', function () {
    $broadcast = Broadcast::factory()->create();

    (new DeleteBroadcastAction)->execute($broadcast);

    expect(Broadcast::query()->find($broadcast->id))->toBeNull();
});

it('deletes a series whose occurrences are all still scheduled, along with them', function () {
    $broadcast = Broadcast::factory()->create();
    $occurrence = BroadcastOccurrence::factory()->fromBroadcast($broadcast)->create();

    (new DeleteBroadcastAction)->execute($broadcast);

    expect(Broadcast::query()->find($broadcast->id))->toBeNull()
        ->and(BroadcastOccurrence::query()->find($occurrence->id))->toBeNull();
});

it('rejects deletion once any occurrence has posted', function () {
    $broadcast = Broadcast::factory()->create();
    BroadcastOccurrence::factory()->fromBroadcast($broadcast)->posted()->create();

    expect(fn () => (new DeleteBroadcastAction)->execute($broadcast))
        ->toThrow(InvalidArgumentException::class);

    expect(Broadcast::query()->find($broadcast->id))->not->toBeNull();
});
