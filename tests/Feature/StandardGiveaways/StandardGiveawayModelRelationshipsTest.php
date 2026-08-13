<?php

declare(strict_types=1);

use App\Models\CollectionThemeItem;
use App\Models\DiscordMember;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayEntry;
use App\Models\StandardGiveawayOccurrence;
use App\Models\StandardGiveawayPrizeItem;
use App\Models\StandardGiveawayRequiredRole;
use App\Models\StandardGiveawayWinner;
use Illuminate\Database\QueryException;

it('relates a guild to its standard giveaways', function () {
    $guild = Guild::factory()->create();
    StandardGiveaway::factory()->for($guild)->create();

    expect($guild->standardGiveaways)->toHaveCount(1);
});

it('relates a standard giveaway to its prize items and required roles', function () {
    $giveaway = StandardGiveaway::factory()->create();
    $item = CollectionThemeItem::factory()->create();

    StandardGiveawayPrizeItem::factory()->for($giveaway, 'standardGiveaway')->create(['collection_theme_item_id' => $item->id]);
    StandardGiveawayRequiredRole::factory()->for($giveaway, 'standardGiveaway')->create();

    expect($giveaway->prizeItems)->toHaveCount(1)
        ->and($giveaway->requiredRoles)->toHaveCount(1);
});

it('enforces one prize item row per giveaway and collection theme item', function () {
    $giveaway = StandardGiveaway::factory()->create();
    $item = CollectionThemeItem::factory()->create();

    StandardGiveawayPrizeItem::factory()->for($giveaway, 'standardGiveaway')->create(['collection_theme_item_id' => $item->id]);

    expect(fn () => StandardGiveawayPrizeItem::factory()->for($giveaway, 'standardGiveaway')->create(['collection_theme_item_id' => $item->id]))
        ->toThrow(QueryException::class);
});

it('enforces one required-role row per giveaway and discord role id', function () {
    $giveaway = StandardGiveaway::factory()->create();

    StandardGiveawayRequiredRole::factory()->for($giveaway, 'standardGiveaway')->create(['discord_role_id' => '111']);

    expect(fn () => StandardGiveawayRequiredRole::factory()->for($giveaway, 'standardGiveaway')->create(['discord_role_id' => '111']))
        ->toThrow(QueryException::class);
});

it('casts prize_item_ids and required_role_ids as arrays', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->create([
        'prize_item_ids' => [1, 2, 3],
        'required_role_ids' => ['111', '222'],
    ]);

    expect($occurrence->fresh()->prize_item_ids)->toBe([1, 2, 3])
        ->and($occurrence->fresh()->required_role_ids)->toBe(['111', '222']);
});

it('reports whether an occurrence has ended based on ends_at, not status', function () {
    $open = StandardGiveawayOccurrence::factory()->posted()->create(['ends_at' => now()->addHour()]);
    $ended = StandardGiveawayOccurrence::factory()->posted()->create(['ends_at' => now()->subMinute()]);
    $notYetPosted = StandardGiveawayOccurrence::factory()->create(['ends_at' => null]);

    expect($open->hasEnded())->toBeFalse()
        ->and($ended->hasEnded())->toBeTrue()
        ->and($notYetPosted->hasEnded())->toBeFalse();
});

it('enforces one entry row per occurrence and member', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->create();
    $member = DiscordMember::factory()->create();

    StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->for($member, 'discordMember')->create();

    expect(fn () => StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->for($member, 'discordMember')->create())
        ->toThrow(QueryException::class);
});

it('relates an entry to its win and reports isWinner correctly', function () {
    $entry = StandardGiveawayEntry::factory()->create();

    expect($entry->isWinner())->toBeFalse();

    StandardGiveawayWinner::factory()->create([
        'standard_giveaway_occurrence_id' => $entry->standard_giveaway_occurrence_id,
        'standard_giveaway_entry_id' => $entry->id,
    ]);

    expect($entry->fresh()->isWinner())->toBeTrue();
});

it('enforces one winner row per occurrence and entry', function () {
    $entry = StandardGiveawayEntry::factory()->create();

    StandardGiveawayWinner::factory()->create([
        'standard_giveaway_occurrence_id' => $entry->standard_giveaway_occurrence_id,
        'standard_giveaway_entry_id' => $entry->id,
    ]);

    expect(fn () => StandardGiveawayWinner::factory()->create([
        'standard_giveaway_occurrence_id' => $entry->standard_giveaway_occurrence_id,
        'standard_giveaway_entry_id' => $entry->id,
    ]))->toThrow(QueryException::class);
});
