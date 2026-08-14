<?php

declare(strict_types=1);

use App\Livewire\StandardGiveaways\OccurrenceDashboard;
use App\Models\CollectionThemeItem;
use App\Models\DiscordMember;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayEntry;
use App\Models\StandardGiveawayOccurrence;
use App\Models\StandardGiveawayWinner;
use App\Models\User;
use Livewire\Livewire;

function occurrenceWithEntriesAndWinners(): array
{
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create();
    $guild = $occurrence->standardGiveaway->guild;

    $winnerMember = DiscordMember::factory()->for($guild)->create(['username' => 'ZeldaWinner']);
    $entrantMember = DiscordMember::factory()->for($guild)->create(['username' => 'LinkEntrant']);

    $winnerEntry = StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->for($winnerMember, 'discordMember')->create();
    $entrantEntry = StandardGiveawayEntry::factory()->for($occurrence, 'standardGiveawayOccurrence')->for($entrantMember, 'discordMember')->create();

    $item = CollectionThemeItem::factory()->create(['name' => 'Golden Coin']);
    $winner = StandardGiveawayWinner::factory()
        ->for($occurrence, 'standardGiveawayOccurrence')
        ->for($winnerEntry, 'standardGiveawayEntry')
        ->for($item, 'collectionThemeItem')
        ->create();

    return compact('occurrence', 'winnerMember', 'entrantMember', 'winner', 'item');
}

it('shows who created the standard giveaway when known', function () {
    $creator = User::factory()->create(['name' => 'Ada Admin']);
    $giveaway = StandardGiveaway::factory()->createdBy($creator)->create();
    $occurrence = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->posted()->create();
    $staff = actingEventStaffFor($giveaway->guild);

    Livewire::actingAs($staff)
        ->test(OccurrenceDashboard::class, ['occurrence' => $occurrence])
        ->assertSee('Created by Ada Admin');
});

it('shows entrants and drawn winners with their prize item', function () {
    ['occurrence' => $occurrence, 'winnerMember' => $winnerMember, 'entrantMember' => $entrantMember, 'item' => $item] = occurrenceWithEntriesAndWinners();
    $staff = actingEventStaffFor($occurrence->standardGiveaway->guild);

    Livewire::actingAs($staff)
        ->test(OccurrenceDashboard::class, ['occurrence' => $occurrence])
        ->assertSee($winnerMember->username)
        ->assertSee($entrantMember->username)
        ->assertSee($item->name);
});

it('filters entrants and winners by search term', function () {
    ['occurrence' => $occurrence, 'winnerMember' => $winnerMember, 'entrantMember' => $entrantMember] = occurrenceWithEntriesAndWinners();
    $staff = actingEventStaffFor($occurrence->standardGiveaway->guild);

    Livewire::actingAs($staff)
        ->test(OccurrenceDashboard::class, ['occurrence' => $occurrence])
        ->set('search', 'zelda')
        ->assertSee($winnerMember->username)
        ->assertDontSee($entrantMember->username);
});

it('denies access to a user who does not admin the occurrence guild', function () {
    ['occurrence' => $occurrence] = occurrenceWithEntriesAndWinners();
    $outsider = User::factory()->create();

    Livewire::actingAs($outsider)
        ->test(OccurrenceDashboard::class, ['occurrence' => $occurrence])
        ->assertForbidden();
});
