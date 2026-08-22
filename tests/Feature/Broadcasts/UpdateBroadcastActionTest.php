<?php

declare(strict_types=1);

use App\Actions\Broadcasts\UpdateBroadcastAction;
use App\Models\Broadcast;
use App\Models\BroadcastOccurrence;

it('updates the broadcast\'s own attributes', function () {
    $broadcast = Broadcast::factory()->create(['title' => 'Old Title']);

    $updated = (new UpdateBroadcastAction)->execute($broadcast, [
        'title' => 'New Title',
        'message_template' => 'New template {{date}}',
        'channel_id' => '999',
    ]);

    expect($updated->title)->toBe('New Title')
        ->and($updated->message_template)->toBe('New template {{date}}')
        ->and($updated->channel_id)->toBe('999');
});

it('does not mutate an already-generated occurrence\'s snapshotted values', function () {
    $broadcast = Broadcast::factory()->create(['message_template' => 'Original template']);
    $occurrence = BroadcastOccurrence::factory()->fromBroadcast($broadcast)->create();

    (new UpdateBroadcastAction)->execute($broadcast, ['message_template' => 'Changed template']);

    expect($occurrence->fresh()->message_template)->toBe('Original template');
});

it('ignores attributes not in the allowed set', function () {
    $broadcast = Broadcast::factory()->create(['status' => Broadcast::STATUS_ACTIVE]);

    (new UpdateBroadcastAction)->execute($broadcast, ['status' => Broadcast::STATUS_CANCELLED]);

    expect($broadcast->fresh()->status)->toBe(Broadcast::STATUS_ACTIVE);
});
