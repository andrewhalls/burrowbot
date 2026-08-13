<?php

declare(strict_types=1);

use App\Models\CollectionTheme;
use App\Models\CollectionThemeItem;
use App\Models\DiscordMember;
use App\Models\DiscordOutboundAction;
use App\Models\Giveaway;
use App\Models\GiveawayEntry;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Illuminate\Database\QueryException;

it('relates a guild to its admins, members, collection themes, and giveaways', function () {
    $guild = Guild::factory()->create();

    GuildAdmin::factory()->for($guild)->create();
    DiscordMember::factory()->for($guild)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    Giveaway::factory()->for($guild)->for($theme, 'collectionTheme')->create();

    expect($guild->guildAdmins)->toHaveCount(1)
        ->and($guild->discordMembers)->toHaveCount(1)
        ->and($guild->collectionThemes)->toHaveCount(1)
        ->and($guild->giveaways)->toHaveCount(1);
});

it('lets a user check whether they admin a given guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    $otherGuild = Guild::factory()->create();

    GuildAdmin::factory()->for($guild)->for($user)->create();

    expect($user->isAdminOfGuild($guild))->toBeTrue()
        ->and($user->isAdminOfGuild($otherGuild))->toBeFalse();
});

it('enforces one guild_admins row per guild and user', function () {
    $guild = Guild::factory()->create();
    $user = User::factory()->create();

    GuildAdmin::factory()->for($guild)->for($user)->create();

    expect(fn () => GuildAdmin::factory()->for($guild)->for($user)->create())
        ->toThrow(QueryException::class);
});

it('enforces one discord_members row per guild and discord user id', function () {
    $guild = Guild::factory()->create();

    DiscordMember::factory()->for($guild)->create(['discord_user_id' => '123']);

    expect(fn () => DiscordMember::factory()->for($guild)->create(['discord_user_id' => '123']))
        ->toThrow(QueryException::class);
});

it('orders collection theme items by sort_order', function () {
    $theme = CollectionTheme::factory()->create();

    CollectionThemeItem::factory()->for($theme, 'collectionTheme')->create(['name' => 'Third', 'sort_order' => 2]);
    CollectionThemeItem::factory()->for($theme, 'collectionTheme')->create(['name' => 'First', 'sort_order' => 0]);
    CollectionThemeItem::factory()->for($theme, 'collectionTheme')->create(['name' => 'Second', 'sort_order' => 1]);

    expect($theme->items->pluck('name')->all())->toBe(['First', 'Second', 'Third']);
});

it('reports a collection theme as not editable while it backs an active giveaway', function () {
    $theme = CollectionTheme::factory()->create();

    expect($theme->isEditable())->toBeTrue();

    Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();

    expect($theme->fresh()->isEditable())->toBeFalse();
});

it('reports a collection theme as editable again once its giveaway is closed', function () {
    $theme = CollectionTheme::factory()->create();
    Giveaway::factory()->for($theme, 'collectionTheme')->closed()->create();

    expect($theme->isEditable())->toBeTrue();
});

it('reports whether a giveaway has expired based on ends_at, not status', function () {
    $active = Giveaway::factory()->active()->create(['ends_at' => now()->addMinutes(10)]);
    $pastEndsAt = Giveaway::factory()->active()->create(['ends_at' => now()->subMinute()]);
    $noEndsAt = Giveaway::factory()->create(['ends_at' => null]);

    expect($active->hasExpired())->toBeFalse()
        ->and($pastEndsAt->hasExpired())->toBeTrue()
        ->and($noEndsAt->hasExpired())->toBeFalse();
});

it('enforces one giveaway_entries row per giveaway and discord member', function () {
    $giveaway = Giveaway::factory()->active()->create();
    $member = DiscordMember::factory()->create();

    GiveawayEntry::factory()->for($giveaway)->for($member, 'discordMember')->create();

    expect(fn () => GiveawayEntry::factory()->for($giveaway)->for($member, 'discordMember')->create())
        ->toThrow(QueryException::class);
});

it('relates a giveaway entry to its item and the staff member who fulfilled it', function () {
    $entry = GiveawayEntry::factory()->fulfilled()->create();

    expect($entry->isFulfilled())->toBeTrue()
        ->and($entry->collectionThemeItem)->toBeInstanceOf(CollectionThemeItem::class)
        ->and($entry->fulfilledBy)->toBeInstanceOf(User::class);
});

it('reports an unfulfilled entry correctly', function () {
    $entry = GiveawayEntry::factory()->create();

    expect($entry->isFulfilled())->toBeFalse();
});

it('relates a discord outbound action to its giveaway and reports pending status', function () {
    $action = DiscordOutboundAction::factory()->create();

    expect($action->giveaway)->toBeInstanceOf(Giveaway::class)
        ->and($action->isPending())->toBeTrue();
});
