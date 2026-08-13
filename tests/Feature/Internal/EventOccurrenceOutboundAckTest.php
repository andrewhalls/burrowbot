<?php

declare(strict_types=1);

use App\Models\DiscordOutboundAction;
use App\Models\EventOccurrence;

it('stamps discord_message_id on the occurrence when acking a message-mode post', function () {
    $occurrence = EventOccurrence::factory()->create(['posting_mode' => 'message']);
    $action = DiscordOutboundAction::factory()->forEventOccurrence($occurrence)->create([
        'type' => DiscordOutboundAction::TYPE_POST_EVENT_OCCURRENCE_MESSAGE,
    ]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/outbound-actions/{$action->id}/ack", ['discord_message_id' => 'msg-1'])
        ->assertStatus(200);

    expect($occurrence->fresh()->discord_message_id)->toBe('msg-1');
});

it('stamps discord_thread_id on the occurrence when acking a thread-mode post', function () {
    $occurrence = EventOccurrence::factory()->create(['posting_mode' => 'thread']);
    $action = DiscordOutboundAction::factory()->forEventOccurrence($occurrence)->create([
        'type' => DiscordOutboundAction::TYPE_POST_EVENT_OCCURRENCE_THREAD,
    ]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/outbound-actions/{$action->id}/ack", ['discord_thread_id' => 'thread-1'])
        ->assertStatus(200);

    expect($occurrence->fresh()->discord_thread_id)->toBe('thread-1');
});
