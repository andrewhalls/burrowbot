<?php

declare(strict_types=1);

use App\Livewire\StandardGiveaways\StandardGiveawayIndex;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use App\Models\User;
use Livewire\Livewire;

it('lists standard giveaways for the guild', function () {
    $guild = Guild::factory()->create();
    StandardGiveaway::factory()->for($guild)->create(['title' => 'Nitro Friday']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Nitro Friday');
});

it('shows a standard giveaway\'s image when set', function () {
    $guild = Guild::factory()->create();
    StandardGiveaway::factory()->for($guild)->withImage('standard-giveaway-images/abc.jpg')->create(['title' => 'Boosted Night']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Boosted Night')
        ->assertSee('standard-giveaway-images/abc.jpg');
});

it('renders cleanly for a standard giveaway with no image', function () {
    $guild = Guild::factory()->create();
    StandardGiveaway::factory()->for($guild)->create(['title' => 'Plain Giveaway']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Plain Giveaway')
        ->assertOk();
});

it('changes a standard giveaway status', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create(['status' => StandardGiveaway::STATUS_ACTIVE]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('setStatus', $giveaway->id, StandardGiveaway::STATUS_PAUSED);

    expect($giveaway->fresh()->status)->toBe(StandardGiveaway::STATUS_PAUSED);
});

it('shows the most recent occurrence dashboard in the detail panel when a tile is selected', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create(['scheduled_post_at' => now()->subWeek()]);
    $recent = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create(['scheduled_post_at' => now()]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Select an item from the list')
        ->call('select', $giveaway->id)
        ->assertDontSee('Select an item from the list')
        ->assertSeeLivewire('standard-giveaways.occurrence-dashboard');
});

it('returns to the list-only view on deselect', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->call('deselect')
        ->assertSee('Select an item from the list');
});

it('shows a friendly message when the selected giveaway has no occurrences yet', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->assertSee('No occurrences generated for this giveaway yet.');
});

it('refuses to select a standard giveaway belonging to a different guild', function () {
    $guild = Guild::factory()->create();
    $otherGuild = Guild::factory()->create();
    $otherGiveaway = StandardGiveaway::factory()->for($otherGuild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $otherGiveaway->id)
        ->assertSee('Select an item from the list');
});

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->assertForbidden();
});
