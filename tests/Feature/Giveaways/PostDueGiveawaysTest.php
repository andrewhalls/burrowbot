<?php

declare(strict_types=1);

use App\Jobs\PostGiveawayMessage;
use App\Models\DiscordOutboundAction;
use App\Models\Giveaway;
use Illuminate\Support\Facades\Queue;

it('starts a draft giveaway whose scheduled_start_at has passed', function () {
    $due = Giveaway::factory()->scheduledFor(now()->subMinute())->create();

    $this->artisan('giveaways:post-due')->assertSuccessful();

    expect($due->fresh()->isActive())->toBeTrue();
});

it('does not touch a giveaway whose scheduled_start_at has not arrived yet', function () {
    $notYet = Giveaway::factory()->scheduledFor(now()->addHour())->create();

    $this->artisan('giveaways:post-due')->assertSuccessful();

    expect($notYet->fresh()->isDraft())->toBeTrue();
});

it('does not touch a draft giveaway with no scheduled_start_at at all', function () {
    $manualOnly = Giveaway::factory()->create();

    $this->artisan('giveaways:post-due')->assertSuccessful();

    expect($manualOnly->fresh()->isDraft())->toBeTrue();
});

it('does not double-start when run twice', function () {
    $due = Giveaway::factory()->scheduledFor(now()->subMinute())->create();

    $this->artisan('giveaways:post-due');
    $startedAt = $due->fresh()->starts_at;
    $this->artisan('giveaways:post-due');

    expect($due->fresh()->starts_at)->toEqual($startedAt)
        ->and(DiscordOutboundAction::query()->where('giveaway_id', $due->id)->count())->toBe(1);
});

it('dispatches a PostGiveawayMessage job for each started giveaway', function () {
    Queue::fake();
    $due = Giveaway::factory()->scheduledFor(now()->subMinute())->create();

    $this->artisan('giveaways:post-due');

    Queue::assertPushedOn('discord-outbound', PostGiveawayMessage::class);
});
