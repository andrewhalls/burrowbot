<?php

declare(strict_types=1);

use App\Livewire\StandardGiveaways\CreateStandardGiveaway;
use App\Models\CollectionTheme;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\User;
use Livewire\Livewire;

it('creates a one-off standard giveaway', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create(['name' => 'Retro Arcade']);
    $item = $theme->items->first();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('title', 'Nitro Friday')
        ->set('description', 'One lucky booster')
        ->set('channelId', '123456')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('addPrizeItem', $item->id)
        ->call('save')
        ->assertDispatched('standard-giveaway-created')
        ->assertHasNoErrors();

    $giveaway = StandardGiveaway::query()->where('title', 'Nitro Friday')->first();
    expect($giveaway)->not->toBeNull()
        ->and($giveaway->occurrences)->toHaveCount(1);
});

it('surfaces search results for prize items scoped to the guild', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(0)->create();
    $theme->items()->create(['name' => 'Golden Ticket', 'sort_order' => 0]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('prizeItemSearch', 'golden')
        ->assertSee('Golden Ticket');
});

it('rejects saving with zero prize items selected', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('title', 'Empty')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertHasErrors('selectedPrizeItemIds');

    expect(StandardGiveaway::query()->count())->toBe(0);
});

it('denies mounting the component for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->assertForbidden();
});
