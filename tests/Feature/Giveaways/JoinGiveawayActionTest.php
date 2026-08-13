<?php

declare(strict_types=1);

use App\Actions\Giveaways\JoinGiveawayAction;
use App\Models\CollectionTheme;
use App\Models\DiscordMember;
use App\Models\Giveaway;
use App\Models\GiveawayEntry;
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
