<?php

declare(strict_types=1);

use App\Actions\StandardGiveaways\CloseAndDrawStandardGiveawayOccurrenceAction;
use App\Models\CollectionTheme;
use App\Models\DiscordMember;
use App\Models\DiscordOutboundAction;
use App\Models\StandardGiveawayEntry;
use App\Models\StandardGiveawayOccurrence;
use App\Models\StandardGiveawayWinner;

function closeAndDrawAction(): CloseAndDrawStandardGiveawayOccurrenceAction
{
    return app(CloseAndDrawStandardGiveawayOccurrenceAction::class);
}

it('draws the configured number of winners from eligible entrants', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $item = $theme->items->first();
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create([
        'winner_count' => 2,
        'prize_item_ids' => [$item->id],
    ]);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->count(3)->create();

    closeAndDrawAction()->execute($occurrence);

    expect($occurrence->fresh()->status)->toBe(StandardGiveawayOccurrence::STATUS_CLOSED)
        ->and(StandardGiveawayWinner::query()->where('standard_giveaway_occurrence_id', $occurrence->id)->count())->toBe(2);
});

it('draws every eligible entrant when fewer exist than the winner count', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create(['winner_count' => 5]);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->count(2)->create();

    closeAndDrawAction()->execute($occurrence);

    expect(StandardGiveawayWinner::query()->where('standard_giveaway_occurrence_id', $occurrence->id)->count())->toBe(2);
});

it('closes with zero winners when there are no entrants', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create(['winner_count' => 3]);

    closeAndDrawAction()->execute($occurrence);

    expect($occurrence->fresh()->status)->toBe(StandardGiveawayOccurrence::STATUS_CLOSED)
        ->and(StandardGiveawayWinner::query()->where('standard_giveaway_occurrence_id', $occurrence->id)->count())->toBe(0);
});

it('assigns each winner a distinct item when more items than winners', function () {
    $theme = CollectionTheme::factory()->withItems(3)->create();
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create([
        'winner_count' => 2,
        'prize_item_ids' => $theme->items->pluck('id')->all(),
    ]);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->count(2)->create();

    closeAndDrawAction()->execute($occurrence);

    $itemIds = StandardGiveawayWinner::query()->where('standard_giveaway_occurrence_id', $occurrence->id)->pluck('collection_theme_item_id');
    expect($itemIds->unique())->toHaveCount(2);
});

it('repeats items once the prize pool is exhausted with more winners than items', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $item = $theme->items->first();
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create([
        'winner_count' => 3,
        'prize_item_ids' => [$item->id],
    ]);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->count(3)->create();

    closeAndDrawAction()->execute($occurrence);

    $itemIds = StandardGiveawayWinner::query()->where('standard_giveaway_occurrence_id', $occurrence->id)->pluck('collection_theme_item_id');
    expect($itemIds->every(fn ($id) => $id === $item->id))->toBeTrue()
        ->and($itemIds)->toHaveCount(3);
});

it('enqueues an announce-winners outbound action naming each winner and their item', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $item = $theme->items->first();
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create([
        'winner_count' => 1,
        'prize_item_ids' => [$item->id],
    ]);
    $member = DiscordMember::factory()->create(['username' => 'LuckyWinner', 'display_name' => 'Lucky Winner']);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->for($member, 'discordMember')->create();

    closeAndDrawAction()->execute($occurrence);

    $action = DiscordOutboundAction::query()
        ->where('standard_giveaway_occurrence_id', $occurrence->id)
        ->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS)
        ->first();

    expect($action)->not->toBeNull()
        ->and($action->payload['winners'][0]['display_name'])->toBe('Lucky Winner')
        ->and($action->payload['winners'][0]['item_name'])->toBe($item->name);
});

it('falls back to username in the announce-winners payload when no display name is recorded', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $item = $theme->items->first();
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create([
        'winner_count' => 1,
        'prize_item_ids' => [$item->id],
    ]);
    $member = DiscordMember::factory()->create(['username' => 'LuckyWinner', 'display_name' => null]);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->for($member, 'discordMember')->create();

    closeAndDrawAction()->execute($occurrence);

    $action = DiscordOutboundAction::query()
        ->where('standard_giveaway_occurrence_id', $occurrence->id)
        ->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS)
        ->first();

    expect($action->payload['winners'][0]['display_name'])->toBe('LuckyWinner');
});

it('includes the won item\'s image url in the outbound payload when it has one', function () {
    $theme = CollectionTheme::factory()->withItems(0)->create();
    $item = $theme->items()->create(['name' => 'Golden Ticket', 'image_path' => 'theme-item-images/abc.jpg', 'sort_order' => 0]);
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create([
        'winner_count' => 1,
        'prize_item_ids' => [$item->id],
    ]);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->create();

    closeAndDrawAction()->execute($occurrence);

    $action = DiscordOutboundAction::query()
        ->where('standard_giveaway_occurrence_id', $occurrence->id)
        ->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS)
        ->first();

    expect($action->payload['winners'][0]['item_image_url'])->toContain('theme-item-images/abc.jpg');
});

it('leaves item_image_url null when the won item has no image', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $item = $theme->items->first();
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create([
        'winner_count' => 1,
        'prize_item_ids' => [$item->id],
    ]);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->create();

    closeAndDrawAction()->execute($occurrence);

    $action = DiscordOutboundAction::query()
        ->where('standard_giveaway_occurrence_id', $occurrence->id)
        ->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS)
        ->first();

    expect($action->payload['winners'][0]['item_image_url'])->toBeNull();
});

it('populates the announce-winners payload with content fields and a rendered congrats message when a template and winners exist', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $item = $theme->items->first();
    $occurrence = StandardGiveawayOccurrence::factory()
        ->withBannerImage('standard-giveaway-images/banner.jpg')
        ->ended()
        ->create([
            'winner_count' => 1,
            'prize_item_ids' => [$item->id],
            'claim_link' => 'https://discord.com/channels/1/2',
            'claim_deadline_hours' => 48,
            'congrats_message_template' => 'Congrats {winners}! You won {prize}. Claim at {claim_link} by {claim_deadline}.',
        ]);
    $member = DiscordMember::factory()->create(['discord_user_id' => '999']);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->for($member, 'discordMember')->create();

    closeAndDrawAction()->execute($occurrence);

    $action = DiscordOutboundAction::query()
        ->where('standard_giveaway_occurrence_id', $occurrence->id)
        ->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS)
        ->first();

    expect($action->payload['title'])->toBe($occurrence->title)
        ->and($action->payload['banner_image_url'])->toContain('standard-giveaway-images/banner.jpg')
        ->and($action->payload['prize_item_names'])->toBe([$item->name])
        ->and($action->payload['congrats_message'])->toContain('<@999>')
        ->and($action->payload['congrats_message'])->toContain($item->name)
        ->and($action->payload['congrats_message'])->toContain('https://discord.com/channels/1/2');

    $claimDeadlineAt = \Illuminate\Support\Carbon::parse($action->payload['claim_deadline_at']);
    $hoursFromNow = abs(now()->diffInHours($claimDeadlineAt, absolute: false));
    expect($hoursFromNow)->toBeLessThanOrEqual(48)
        ->and($hoursFromNow)->toBeGreaterThan(47);
});

it('leaves congrats_message null when the occurrence has no congrats_message_template', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create(['winner_count' => 1, 'congrats_message_template' => null]);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->create();

    closeAndDrawAction()->execute($occurrence);

    $action = DiscordOutboundAction::query()
        ->where('standard_giveaway_occurrence_id', $occurrence->id)
        ->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS)
        ->first();

    expect($action->payload['congrats_message'])->toBeNull();
});

it('leaves congrats_message null when there are zero winners, even with a template configured', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create([
        'winner_count' => 3,
        'congrats_message_template' => 'Congrats {winners}!',
    ]);

    closeAndDrawAction()->execute($occurrence);

    $action = DiscordOutboundAction::query()
        ->where('standard_giveaway_occurrence_id', $occurrence->id)
        ->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS)
        ->first();

    expect($action->payload['congrats_message'])->toBeNull();
});

it('leaves claim_deadline_at null when the occurrence has no claim_deadline_hours', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create(['winner_count' => 1, 'claim_deadline_hours' => null]);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->create();

    closeAndDrawAction()->execute($occurrence);

    $action = DiscordOutboundAction::query()
        ->where('standard_giveaway_occurrence_id', $occurrence->id)
        ->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS)
        ->first();

    expect($action->payload['claim_deadline_at'])->toBeNull();
});

it('is idempotent - running twice does not double-draw', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->ended()->create(['winner_count' => 1]);
    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->create();

    closeAndDrawAction()->execute($occurrence);
    closeAndDrawAction()->execute($occurrence->fresh());

    expect(StandardGiveawayWinner::query()->where('standard_giveaway_occurrence_id', $occurrence->id)->count())->toBe(1);
});

it('does not close an occurrence that has not yet reached its end time', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create(['ends_at' => now()->addHour()]);

    closeAndDrawAction()->execute($occurrence);

    expect($occurrence->fresh()->status)->toBe(StandardGiveawayOccurrence::STATUS_POSTED);
});
