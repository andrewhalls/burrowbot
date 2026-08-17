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

it('shows who created a standard giveaway when known', function () {
    $guild = Guild::factory()->create();
    $creator = User::factory()->create(['name' => 'Ada Admin']);
    StandardGiveaway::factory()->for($guild)->createdBy($creator)->create(['title' => 'Nitro Friday']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Created by Ada Admin');
});

it('deletes a series with no posted occurrences', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->call('delete')
        ->assertSet('selectedId', null);

    expect(StandardGiveaway::query()->find($giveaway->id))->toBeNull();
});

it('does not offer delete, and rejects it server-side, once a series has a posted occurrence', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->posted()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->assertDontSeeHtml('wire:click="delete"')
        ->call('delete')
        ->assertHasErrors('delete');

    expect(StandardGiveaway::query()->find($giveaway->id))->not->toBeNull();
});

it('shows an upcoming occurrences list only when the series has scheduled occurrences', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->assertDontSee('Upcoming occurrences');

    $occurrence = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'description' => 'Next week\'s prize',
        'scheduled_post_at' => now()->addWeek(),
    ]);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->assertSee('Upcoming occurrences')
        ->assertSee('Next week\'s prize');
});

it('toggles into and out of editing a specific upcoming occurrence', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    $occurrence = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'scheduled_post_at' => now()->addWeek(),
    ]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->assertDontSeeLivewire('standard-giveaways.edit-standard-giveaway-occurrence')
        ->call('toggleEditOccurrence', $occurrence->id)
        ->assertSeeLivewire('standard-giveaways.edit-standard-giveaway-occurrence')
        ->call('toggleEditOccurrence', $occurrence->id)
        ->assertDontSeeLivewire('standard-giveaways.edit-standard-giveaway-occurrence');
});

it('refuses to edit a posted occurrence directly, and closes an open occurrence edit on reselect', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    $scheduled = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->create([
        'status' => StandardGiveawayOccurrence::STATUS_SCHEDULED,
        'scheduled_post_at' => now()->addWeek(),
    ]);
    $posted = StandardGiveawayOccurrence::factory()->for($giveaway, 'standardGiveaway')->posted()->create([
        'scheduled_post_at' => now()->subWeek(),
    ]);
    $staff = actingEventStaffFor($guild);

    expect(fn () => Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->call('toggleEditOccurrence', $posted->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    $component = Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->call('toggleEditOccurrence', $scheduled->id)
        ->assertSeeLivewire('standard-giveaways.edit-standard-giveaway-occurrence');

    $component->call('select', $giveaway->id)
        ->assertSet('editingOccurrenceId', null)
        ->assertDontSeeLivewire('standard-giveaways.edit-standard-giveaway-occurrence');
});

it('toggles into and out of the edit series form for the selected giveaway', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->assertDontSeeLivewire('standard-giveaways.edit-standard-giveaway')
        ->call('toggleEditSeries')
        ->assertSeeLivewire('standard-giveaways.edit-standard-giveaway')
        ->call('toggleEditSeries')
        ->assertDontSeeLivewire('standard-giveaways.edit-standard-giveaway');
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

it('excludes archived giveaways from the list by default, and shows them when Show archived is toggled on', function () {
    $guild = Guild::factory()->create();
    $active = StandardGiveaway::factory()->for($guild)->create(['title' => 'Active Giveaway']);
    $archived = StandardGiveaway::factory()->for($guild)->archived()->create(['title' => 'Old Giveaway']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Active Giveaway')
        ->assertDontSee('Old Giveaway')
        ->set('showArchived', true)
        ->assertSee('Active Giveaway')
        ->assertSee('Old Giveaway');
});

it('archives a standard giveaway, cancelling it and hiding it from the default list', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create(['status' => StandardGiveaway::STATUS_ACTIVE]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('archive', $giveaway->id);

    expect($giveaway->fresh()->status)->toBe(StandardGiveaway::STATUS_CANCELLED)
        ->and($giveaway->fresh()->archived_at)->not->toBeNull();
});

it('unarchives a standard giveaway, leaving its status untouched', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->archived()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('unarchive', $giveaway->id);

    expect($giveaway->fresh()->archived_at)->toBeNull()
        ->and($giveaway->fresh()->status)->toBe(StandardGiveaway::STATUS_CANCELLED);
});

it('offers Edit series and Unarchive for an archived giveaway once shown', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->archived()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->set('showArchived', true)
        ->call('select', $giveaway->id)
        ->assertSeeHtml('wire:click="toggleEditSeries"')
        ->assertSeeHtml('wire:click.stop="unarchive('.$giveaway->id.')"');
});

it('refuses to archive a standard giveaway belonging to a different guild', function () {
    $guild = Guild::factory()->create();
    $otherGuild = Guild::factory()->create();
    $otherGiveaway = StandardGiveaway::factory()->for($otherGuild)->create();
    $staff = actingEventStaffFor($guild);

    expect(fn () => Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('archive', $otherGiveaway->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
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

it('opening the create form deselects the current giveaway, and selecting a tile closes the create form', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    $component = Livewire::actingAs($staff)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->call('toggleCreateForm');

    $component->assertSet('selectedId', null)
        ->assertSeeLivewire('standard-giveaways.create-standard-giveaway');

    $component->call('select', $giveaway->id)
        ->assertSet('showCreateForm', false)
        ->assertDontSeeLivewire('standard-giveaways.create-standard-giveaway');
});

it('selects the newly created giveaway after submitting the create form', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    $component = Livewire::actingAs($staff)->test(StandardGiveawayIndex::class, ['guild' => $guild]);
    $component->call('toggleCreateForm');

    $giveaway = StandardGiveaway::factory()->for($guild)->create();
    $component->dispatch('standard-giveaway-created', giveawayId: $giveaway->id);

    $component->assertSet('showCreateForm', false)
        ->assertSet('selectedId', $giveaway->id);
});

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(StandardGiveawayIndex::class, ['guild' => $guild])
        ->assertForbidden();
});
