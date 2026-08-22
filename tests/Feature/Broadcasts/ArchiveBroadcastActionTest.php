<?php

declare(strict_types=1);

use App\Actions\Broadcasts\ArchiveBroadcastAction;
use App\Actions\Broadcasts\UnarchiveBroadcastAction;
use App\Models\Broadcast;

it('archives an active broadcast, cancelling it', function () {
    $broadcast = Broadcast::factory()->create(['status' => Broadcast::STATUS_ACTIVE]);

    (new ArchiveBroadcastAction)->execute($broadcast);

    expect($broadcast->fresh()->status)->toBe(Broadcast::STATUS_CANCELLED)
        ->and($broadcast->fresh()->archived_at)->not->toBeNull();
});

it('archives an already-cancelled broadcast without changing its status', function () {
    $broadcast = Broadcast::factory()->cancelled()->create();

    (new ArchiveBroadcastAction)->execute($broadcast);

    expect($broadcast->fresh()->status)->toBe(Broadcast::STATUS_CANCELLED)
        ->and($broadcast->fresh()->archived_at)->not->toBeNull();
});

it('unarchiving clears only the archived marker, leaving status untouched', function () {
    $broadcast = Broadcast::factory()->archived()->create();

    (new UnarchiveBroadcastAction)->execute($broadcast);

    expect($broadcast->fresh()->archived_at)->toBeNull()
        ->and($broadcast->fresh()->status)->toBe(Broadcast::STATUS_CANCELLED);
});
