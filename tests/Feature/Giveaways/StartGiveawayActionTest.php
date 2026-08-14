<?php

declare(strict_types=1);

use App\Actions\Giveaways\StartGiveawayAction;
use App\Jobs\PostGiveawayMessage;
use App\Models\DiscordOutboundAction;
use App\Models\Giveaway;
use Illuminate\Support\Facades\Queue;

it('activates a draft giveaway and computes ends_at from the start time', function () {
    $giveaway = Giveaway::factory()->create(['duration_minutes' => 45]);

    $result = (new StartGiveawayAction)->execute($giveaway);

    expect($result->isActive())->toBeTrue()
        ->and($result->starts_at)->not->toBeNull()
        ->and($result->starts_at->diffInMinutes($result->ends_at))->toEqual(45);
});

it('dispatches a PostGiveawayMessage job on the discord-outbound queue', function () {
    Queue::fake();

    $giveaway = Giveaway::factory()->create();

    (new StartGiveawayAction)->execute($giveaway);

    Queue::assertPushedOn('discord-outbound', PostGiveawayMessage::class);
});

it('creates a pending outbound action when the job runs', function () {
    $giveaway = Giveaway::factory()->create();

    (new StartGiveawayAction)->execute($giveaway);

    expect(DiscordOutboundAction::query()
        ->where('giveaway_id', $giveaway->id)
        ->where('type', DiscordOutboundAction::TYPE_POST_GIVEAWAY_MESSAGE)
        ->where('status', DiscordOutboundAction::STATUS_PENDING)
        ->exists())->toBeTrue();
});

it('includes the description and image url in the outbound action payload when set', function () {
    $giveaway = Giveaway::factory()->withDescription('A special one')->withImage('giveaway-images/abc.jpg')->create();

    (new StartGiveawayAction)->execute($giveaway);

    $action = DiscordOutboundAction::query()->where('giveaway_id', $giveaway->id)->first();
    expect($action->payload['description'])->toBe('A special one')
        ->and($action->payload['image_url'])->toContain('giveaway-images/abc.jpg');
});

it('omits the description and leaves image_url null when neither is set', function () {
    $giveaway = Giveaway::factory()->create();

    (new StartGiveawayAction)->execute($giveaway);

    $action = DiscordOutboundAction::query()->where('giveaway_id', $giveaway->id)->first();
    expect($action->payload['description'])->toBeNull()
        ->and($action->payload['image_url'])->toBeNull();
});

it('refuses to start a giveaway that is not a draft', function () {
    $giveaway = Giveaway::factory()->active()->create();

    expect(fn () => (new StartGiveawayAction)->execute($giveaway))
        ->toThrow(InvalidArgumentException::class);
});
