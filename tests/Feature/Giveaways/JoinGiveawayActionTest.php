<?php

declare(strict_types=1);

use App\Actions\Giveaways\JoinGiveawayAction;
use App\Models\CollectionTheme;
use App\Models\DiscordMember;
use App\Models\DiscordOutboundAction;
use App\Models\Giveaway;
use App\Models\GiveawayEntry;
use App\Models\Guild;
use App\Support\Giveaways\JoinResult;

function joinAction(): JoinGiveawayAction
{
    return app(JoinGiveawayAction::class);
}

it('assigns a random item on first join', function () {
    $theme = CollectionTheme::factory()->withItems(3)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    $result = joinAction()->execute($giveaway, '111', 'newbie');

    expect($result->status)->toBe(JoinResult::STATUS_WON)
        ->and($theme->items->pluck('id')->all())->toContain($result->item->id);

    $entry = GiveawayEntry::query()->first();
    expect($entry->collection_theme_item_id)->toBe($result->item->id);
});

it('creates the discord member record if it does not already exist', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    joinAction()->execute($giveaway, '222', 'first-timer');

    expect(DiscordMember::query()
        ->where('guild_id', $giveaway->guild_id)
        ->where('discord_user_id', '222')
        ->where('username', 'first-timer')
        ->exists())->toBeTrue();
});

it('does not create a second entry or re-roll on a duplicate join', function () {
    $theme = CollectionTheme::factory()->withItems(5)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    $first = joinAction()->execute($giveaway, '333', 'repeat-clicker');
    $second = joinAction()->execute($giveaway, '333', 'repeat-clicker');

    expect($second->status)->toBe(JoinResult::STATUS_ALREADY_ENTERED)
        ->and($second->item->id)->toBe($first->item->id)
        ->and(GiveawayEntry::query()->count())->toBe(1);
});

it('enqueues a per-winner outbound action when both winner-message fields are configured', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')
        ->active()
        ->withWinnerMessage('987654', 'Congrats {winner}! You won {prize}.')
        ->create();

    $result = joinAction()->execute($giveaway, '111', 'winner-name');

    $action = DiscordOutboundAction::query()
        ->where('giveaway_id', $giveaway->id)
        ->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_GIVEAWAY_WINNER)
        ->first();

    expect($action)->not->toBeNull()
        ->and($action->payload['channel_id'])->toBe('987654')
        ->and($action->payload['message'])->toBe("Congrats <@111>! You won {$result->item->name}.");
});

it('does not enqueue a per-winner outbound action when the guild\'s flag is disabled, even with both fields configured', function () {
    $guild = Guild::factory()->withPopupGiveawayWinnerMessagesDisabled()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($guild)->for($theme, 'collectionTheme')
        ->active()
        ->withWinnerMessage('987654', 'Congrats {winner}!')
        ->create();

    joinAction()->execute($giveaway, '111', 'winner-name');

    expect(DiscordOutboundAction::query()->where('giveaway_id', $giveaway->id)->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_GIVEAWAY_WINNER)->count())->toBe(0);
});

it('does not enqueue a per-winner outbound action when neither winner-message field is configured', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    joinAction()->execute($giveaway, '111', 'winner-name');

    expect(DiscordOutboundAction::query()->where('giveaway_id', $giveaway->id)->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_GIVEAWAY_WINNER)->count())->toBe(0);
});

it('does not enqueue a per-winner outbound action when only the channel is configured', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create(['winner_message_channel_id' => '987654']);

    joinAction()->execute($giveaway, '111', 'winner-name');

    expect(DiscordOutboundAction::query()->where('giveaway_id', $giveaway->id)->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_GIVEAWAY_WINNER)->count())->toBe(0);
});

it('does not enqueue a per-winner outbound action for a duplicate join', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')
        ->active()
        ->withWinnerMessage('987654', 'Congrats {winner}!')
        ->create();

    joinAction()->execute($giveaway, '111', 'winner-name');
    joinAction()->execute($giveaway, '111', 'winner-name');

    expect(DiscordOutboundAction::query()->where('giveaway_id', $giveaway->id)->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_GIVEAWAY_WINNER)->count())->toBe(1);
});

it('does not enqueue a per-winner outbound action for an expired join', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')
        ->withWinnerMessage('987654', 'Congrats {winner}!')
        ->create([
            'status' => Giveaway::STATUS_ACTIVE,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->subMinute(),
        ]);

    joinAction()->execute($giveaway, '111', 'winner-name');

    expect(DiscordOutboundAction::query()->where('giveaway_id', $giveaway->id)->where('type', DiscordOutboundAction::TYPE_ANNOUNCE_GIVEAWAY_WINNER)->count())->toBe(0);
});

it('rejects a join after ends_at even if status has not been flipped to closed yet', function () {
    $theme = CollectionTheme::factory()->withItems(1)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->create([
        'status' => Giveaway::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinute(), // expired, but status column still "active"
    ]);

    $result = joinAction()->execute($giveaway, '444', 'latecomer');

    expect($result->status)->toBe(JoinResult::STATUS_EXPIRED)
        ->and($result->item)->toBeNull()
        ->and(GiveawayEntry::query()->count())->toBe(0);
});

it('rejects a join on a draft giveaway', function () {
    $giveaway = Giveaway::factory()->create();

    $result = joinAction()->execute($giveaway, '555', 'too-early');

    expect($result->status)->toBe(JoinResult::STATUS_EXPIRED);
});

it('rejects a join on a closed giveaway', function () {
    $giveaway = Giveaway::factory()->closed()->create();

    $result = joinAction()->execute($giveaway, '666', 'too-late');

    expect($result->status)->toBe(JoinResult::STATUS_EXPIRED);
});

it('awards every item exactly once before repeating, across many entrants', function () {
    $theme = CollectionTheme::factory()->withItems(3)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    $wonItemIds = [];
    foreach (range(1, 3) as $i) {
        $result = joinAction()->execute($giveaway, "member-{$i}", "member-{$i}");
        $wonItemIds[] = $result->item->id;
    }

    // All three items handed out, no repeats, while entrants <= items.
    expect($wonItemIds)->toEqualCanonicalizing($theme->items->pluck('id')->all());

    // A 4th entrant, with the pool exhausted, still gets an item (a repeat allowed).
    $fourth = joinAction()->execute($giveaway, 'member-4', 'member-4');
    expect($theme->items->pluck('id')->all())->toContain($fourth->item->id);
});
