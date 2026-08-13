<?php

declare(strict_types=1);

use App\Models\DiscordOutboundAction;
use App\Models\Giveaway;

it('lists only pending outbound actions', function () {
    $pending = DiscordOutboundAction::factory()->create(['status' => DiscordOutboundAction::STATUS_PENDING]);
    DiscordOutboundAction::factory()->create(['status' => DiscordOutboundAction::STATUS_ACKED]);

    $this->withHeaders(botAuthHeader())
        ->getJson('/internal/outbound-actions')
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $pending->id);
});

it('only returns actions after the given cursor', function () {
    $first = DiscordOutboundAction::factory()->create();
    $second = DiscordOutboundAction::factory()->create();

    $this->withHeaders(botAuthHeader())
        ->getJson("/internal/outbound-actions?since={$first->id}")
        ->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $second->id);
});

it('acks an action and stamps the giveaway message id for a post action', function () {
    $giveaway = Giveaway::factory()->active()->create();
    $action = DiscordOutboundAction::factory()->create([
        'giveaway_id' => $giveaway->id,
        'type' => DiscordOutboundAction::TYPE_POST_GIVEAWAY_MESSAGE,
    ]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/outbound-actions/{$action->id}/ack", ['discord_message_id' => 'msg-123'])
        ->assertStatus(200)
        ->assertJsonPath('status', 'acked');

    expect($giveaway->fresh()->discord_message_id)->toBe('msg-123');
});

it('marks an action failed and records the reason and attempt count', function () {
    $action = DiscordOutboundAction::factory()->create(['attempts' => 0]);

    $this->withHeaders(botAuthHeader())
        ->postJson("/internal/outbound-actions/{$action->id}/fail", ['reason' => 'discord 500'])
        ->assertStatus(200)
        ->assertJsonPath('status', 'failed')
        ->assertJsonPath('attempts', 1);

    expect($action->fresh()->last_failure_reason)->toBe('discord 500');
});

it('rejects outbound-action requests without a valid bot token', function () {
    $this->getJson('/internal/outbound-actions')->assertStatus(401);
});
