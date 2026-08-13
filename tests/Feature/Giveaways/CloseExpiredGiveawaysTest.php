<?php

declare(strict_types=1);

use App\Jobs\CloseGiveawayMessage;
use App\Models\DiscordOutboundAction;
use App\Models\Giveaway;
use Illuminate\Support\Facades\Queue;

it('closes active giveaways whose ends_at has passed', function () {
    $expired = Giveaway::factory()->active()->create(['ends_at' => now()->subMinute()]);
    $stillRunning = Giveaway::factory()->active()->create(['ends_at' => now()->addMinutes(10)]);

    $this->artisan('giveaways:close-expired')->assertSuccessful();

    expect($expired->fresh()->isClosed())->toBeTrue()
        ->and($stillRunning->fresh()->isActive())->toBeTrue();
});

it('does not touch giveaways that are still draft or already closed', function () {
    $draft = Giveaway::factory()->create();
    $closed = Giveaway::factory()->closed()->create();

    $this->artisan('giveaways:close-expired')->assertSuccessful();

    expect($draft->fresh()->isDraft())->toBeTrue()
        ->and($closed->fresh()->isClosed())->toBeTrue();
});

it('closes an expired giveaway even if it has zero entrants', function () {
    $expired = Giveaway::factory()->active()->create(['ends_at' => now()->subSecond()]);

    $this->artisan('giveaways:close-expired')->assertSuccessful();

    expect($expired->fresh()->isClosed())->toBeTrue();
});

it('dispatches a CloseGiveawayMessage job for each closed giveaway', function () {
    Queue::fake();
    $expired = Giveaway::factory()->active()->create(['ends_at' => now()->subMinute()]);

    $this->artisan('giveaways:close-expired');

    Queue::assertPushedOn('discord-outbound', CloseGiveawayMessage::class);
});

it('creates a pending close outbound action', function () {
    $expired = Giveaway::factory()->active()->create(['ends_at' => now()->subMinute()]);

    $this->artisan('giveaways:close-expired');

    expect(DiscordOutboundAction::query()
        ->where('giveaway_id', $expired->id)
        ->where('type', DiscordOutboundAction::TYPE_CLOSE_GIVEAWAY_MESSAGE)
        ->exists())->toBeTrue();
});
