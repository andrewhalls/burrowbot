<?php

declare(strict_types=1);

use App\Livewire\StandardGiveaways\EditStandardGiveawayOccurrence;
use App\Models\CollectionThemeItem;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use App\Models\User;
use Livewire\Livewire;

it('pre-fills the form from the occurrence', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    $occurrence = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'description' => 'This week\'s prize',
        'prize_item_ids' => [42],
    ]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditStandardGiveawayOccurrence::class, ['occurrence' => $occurrence])
        ->assertSet('description', 'This week\'s prize')
        ->assertSet('selectedPrizeItemIds', [42]);
});

it('saves description and prize item changes scoped to this occurrence', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    $occurrence = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'description' => 'Old',
    ]);
    $item = CollectionThemeItem::factory()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditStandardGiveawayOccurrence::class, ['occurrence' => $occurrence])
        ->set('description', 'New description')
        ->call('removePrizeItem', $occurrence->prize_item_ids[0] ?? 0)
        ->call('addPrizeItem', $item->id)
        ->call('save')
        ->assertDispatched('standard-giveaway-occurrence-updated')
        ->assertHasNoErrors();

    expect($occurrence->fresh()->description)->toBe('New description')
        ->and($occurrence->fresh()->prize_item_ids)->toBe([$item->id]);
});

it('requires at least one prize item', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    $occurrence = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'prize_item_ids' => [],
    ]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditStandardGiveawayOccurrence::class, ['occurrence' => $occurrence])
        ->set('description', 'Something')
        ->call('save')
        ->assertHasErrors('selectedPrizeItemIds');
});

it('denies mounting for a guild the user does not admin', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->create(['status' => StandardGiveawayOccurrence::STATUS_SCHEDULED]);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditStandardGiveawayOccurrence::class, ['occurrence' => $occurrence])
        ->assertForbidden();
});
