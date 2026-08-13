<?php

declare(strict_types=1);

use App\Livewire\Giveaways\GiveawayDashboard;
use App\Models\CollectionTheme;
use App\Models\DiscordMember;
use App\Models\Giveaway;
use App\Models\GiveawayEntry;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

function giveawayWithEntries(): array
{
    $theme = CollectionTheme::factory()->withItems(2)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->active()->create();
    $itemA = $theme->items[0];
    $itemB = $theme->items[1];

    $memberA = DiscordMember::factory()->for($giveaway->guild)->create(['username' => 'ZeldaFan']);
    $memberB = DiscordMember::factory()->for($giveaway->guild)->create(['username' => 'LinkLover']);

    $entryA = GiveawayEntry::factory()->for($giveaway)->for($memberA, 'discordMember')->create(['collection_theme_item_id' => $itemA->id]);
    $entryB = GiveawayEntry::factory()->for($giveaway)->for($memberB, 'discordMember')->fulfilled()->create(['collection_theme_item_id' => $itemB->id]);

    return compact('giveaway', 'theme', 'itemA', 'itemB', 'memberA', 'memberB', 'entryA', 'entryB');
}

function actingStaffFor($guild): User
{
    $user = User::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    return $user;
}

it('lists only entrants for this giveaway', function () {
    ['giveaway' => $giveaway, 'memberA' => $memberA, 'memberB' => $memberB] = giveawayWithEntries();
    $staff = actingStaffFor($giveaway->guild);

    Livewire::actingAs($staff)
        ->test(GiveawayDashboard::class, ['giveaway' => $giveaway])
        ->assertSee($memberA->username)
        ->assertSee($memberB->username);
});

it('filters entrants by search term', function () {
    ['giveaway' => $giveaway, 'memberA' => $memberA, 'memberB' => $memberB] = giveawayWithEntries();
    $staff = actingStaffFor($giveaway->guild);

    Livewire::actingAs($staff)
        ->test(GiveawayDashboard::class, ['giveaway' => $giveaway])
        ->set('search', 'zelda')
        ->assertSee($memberA->username)
        ->assertDontSee($memberB->username);
});

it('filters entrants by item', function () {
    ['giveaway' => $giveaway, 'itemA' => $itemA, 'memberA' => $memberA, 'memberB' => $memberB] = giveawayWithEntries();
    $staff = actingStaffFor($giveaway->guild);

    Livewire::actingAs($staff)
        ->test(GiveawayDashboard::class, ['giveaway' => $giveaway])
        ->set('itemFilter', (string) $itemA->id)
        ->assertSee($memberA->username)
        ->assertDontSee($memberB->username);
});

it('filters entrants by fulfilment status', function () {
    ['giveaway' => $giveaway, 'memberA' => $memberA, 'memberB' => $memberB] = giveawayWithEntries();
    $staff = actingStaffFor($giveaway->guild);

    Livewire::actingAs($staff)
        ->test(GiveawayDashboard::class, ['giveaway' => $giveaway])
        ->set('fulfilmentFilter', 'unfulfilled')
        ->assertSee($memberA->username)
        ->assertDontSee($memberB->username);

    Livewire::actingAs($staff)
        ->test(GiveawayDashboard::class, ['giveaway' => $giveaway])
        ->set('fulfilmentFilter', 'fulfilled')
        ->assertDontSee($memberA->username)
        ->assertSee($memberB->username);
});

it('marks an entry fulfilled', function () {
    ['giveaway' => $giveaway, 'entryA' => $entryA] = giveawayWithEntries();
    $staff = actingStaffFor($giveaway->guild);

    Livewire::actingAs($staff)
        ->test(GiveawayDashboard::class, ['giveaway' => $giveaway])
        ->call('markFulfilled', $entryA->id);

    expect($entryA->fresh()->isFulfilled())->toBeTrue()
        ->and($entryA->fresh()->fulfilled_by_user_id)->toBe($staff->id);
});

it('denies dashboard access to a user who does not admin the giveaway guild', function () {
    ['giveaway' => $giveaway] = giveawayWithEntries();
    $outsider = User::factory()->create();

    Livewire::actingAs($outsider)
        ->test(GiveawayDashboard::class, ['giveaway' => $giveaway])
        ->assertForbidden();
});

it('starts a draft giveaway from the dashboard', function () {
    $giveaway = Giveaway::factory()->create();
    $staff = actingStaffFor($giveaway->guild);

    Livewire::actingAs($staff)
        ->test(GiveawayDashboard::class, ['giveaway' => $giveaway])
        ->call('start');

    expect($giveaway->fresh()->isActive())->toBeTrue();
});

it('denies starting a giveaway to a user who does not admin its guild', function () {
    $giveaway = Giveaway::factory()->create();
    $outsider = User::factory()->create();

    Livewire::actingAs($outsider)
        ->test(GiveawayDashboard::class, ['giveaway' => $giveaway])
        ->assertForbidden();
});
