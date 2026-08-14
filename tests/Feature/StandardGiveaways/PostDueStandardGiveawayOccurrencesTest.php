<?php

declare(strict_types=1);

use App\Models\CollectionThemeItem;
use App\Models\DiscordOutboundAction;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;

it('enqueues a post_standard_giveaway_message action and stamps posted_at/ends_at', function () {
    $item = CollectionThemeItem::factory()->create(['name' => 'Nitro Code']);
    $occurrence = StandardGiveawayOccurrence::factory()->create([
        'posting_mode' => StandardGiveaway::POSTING_MODE_MESSAGE,
        'duration_minutes' => 120,
        'prize_item_ids' => [$item->id],
    ]);

    $this->artisan('standard-giveaways:post-due-occurrences')->assertSuccessful();

    $action = DiscordOutboundAction::query()->where('standard_giveaway_occurrence_id', $occurrence->id)->first();
    expect($action)->not->toBeNull()
        ->and($action->type)->toBe(DiscordOutboundAction::TYPE_POST_STANDARD_GIVEAWAY_MESSAGE)
        ->and($action->payload['prize_item_names'])->toBe(['Nitro Code']);

    $fresh = $occurrence->fresh();
    expect($fresh->status)->toBe(StandardGiveawayOccurrence::STATUS_POSTED)
        ->and($fresh->posted_at)->not->toBeNull()
        ->and($fresh->posted_at->diffInMinutes($fresh->ends_at))->toEqual(120);
});

it('includes the image url in the outbound action payload when set', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->withImage('standard-giveaway-images/abc.jpg')->create();

    $this->artisan('standard-giveaways:post-due-occurrences');

    $action = DiscordOutboundAction::query()->where('standard_giveaway_occurrence_id', $occurrence->id)->first();
    expect($action->payload['image_url'])->toContain('standard-giveaway-images/abc.jpg');
});

it('leaves image_url null when the occurrence has no image', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->create();

    $this->artisan('standard-giveaways:post-due-occurrences');

    $action = DiscordOutboundAction::query()->where('standard_giveaway_occurrence_id', $occurrence->id)->first();
    expect($action->payload['image_url'])->toBeNull();
});

it('enqueues a post_standard_giveaway_thread action for a thread-mode occurrence', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->create(['posting_mode' => StandardGiveaway::POSTING_MODE_THREAD]);

    $this->artisan('standard-giveaways:post-due-occurrences');

    $action = DiscordOutboundAction::query()->where('standard_giveaway_occurrence_id', $occurrence->id)->first();
    expect($action->type)->toBe(DiscordOutboundAction::TYPE_POST_STANDARD_GIVEAWAY_THREAD);
});

it('does not re-post an already-posted occurrence', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create();

    $this->artisan('standard-giveaways:post-due-occurrences');

    expect(DiscordOutboundAction::query()->where('standard_giveaway_occurrence_id', $occurrence->id)->count())->toBe(0);
});
