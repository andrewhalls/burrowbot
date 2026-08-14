<?php

declare(strict_types=1);

use App\Livewire\StandardGiveaways\StandardGiveawayIndex;
use App\Models\Guild;
use App\Models\StandardGiveaway;
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

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->assertForbidden();
});
