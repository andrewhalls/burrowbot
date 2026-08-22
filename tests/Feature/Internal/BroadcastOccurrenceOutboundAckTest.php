<?php

declare(strict_types=1);

use App\Models\BroadcastOccurrence;
use App\Models\DiscordOutboundAction;

it('stamps discord_message_id on the occurrence when acking a broadcast post', function () {
    $occurrence = BroadcastOccurrence::factory()->create();
    $action = DiscordOutboundAction::factory()->forBroadcastOccurrence($occurrence)->create([
        'type' => DiscordOutboundAction::TYPE_POST_BROADCAST_MESSAGE,
    ]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/outbound-actions/{$action->id}/ack", ['discord_message_id' => 'msg-1'])
        ->assertStatus(200);

    expect($occurrence->fresh()->discord_message_id)->toBe('msg-1');
});
