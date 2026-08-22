<?php

declare(strict_types=1);

use App\Livewire\Broadcasts\BroadcastIndex;
use App\Models\Broadcast;
use App\Models\BroadcastOccurrence;
use App\Models\Guild;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

it('lists broadcasts for the guild', function () {
    $guild = Guild::factory()->create();
    Broadcast::factory()->for($guild)->create(['title' => 'Raid Reset']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->assertSee('Raid Reset');
});

it('changes a broadcast status', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create(['status' => Broadcast::STATUS_ACTIVE]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->call('setStatus', $broadcast->id, Broadcast::STATUS_PAUSED);

    expect($broadcast->fresh()->status)->toBe(Broadcast::STATUS_PAUSED);
});

it('excludes archived broadcasts from the list by default, and shows them when Show archived is toggled on', function () {
    $guild = Guild::factory()->create();
    Broadcast::factory()->for($guild)->create(['title' => 'Active Broadcast']);
    Broadcast::factory()->for($guild)->archived()->create(['title' => 'Old Broadcast']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->assertSee('Active Broadcast')
        ->assertDontSee('Old Broadcast')
        ->set('showArchived', true)
        ->assertSee('Active Broadcast')
        ->assertSee('Old Broadcast');
});

it('archives a broadcast, cancelling it and hiding it from the default list', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create(['status' => Broadcast::STATUS_ACTIVE]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->call('archive', $broadcast->id);

    expect($broadcast->fresh()->status)->toBe(Broadcast::STATUS_CANCELLED)
        ->and($broadcast->fresh()->archived_at)->not->toBeNull();
});

it('unarchives a broadcast, leaving its status untouched', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->archived()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->call('unarchive', $broadcast->id);

    expect($broadcast->fresh()->archived_at)->toBeNull()
        ->and($broadcast->fresh()->status)->toBe(Broadcast::STATUS_CANCELLED);
});

it('refuses to archive a broadcast belonging to a different guild', function () {
    $guild = Guild::factory()->create();
    $otherGuild = Guild::factory()->create();
    $otherBroadcast = Broadcast::factory()->for($otherGuild)->create();
    $staff = actingEventStaffFor($guild);

    expect(fn () => Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->call('archive', $otherBroadcast->id))
        ->toThrow(ModelNotFoundException::class);
});

it('shows who created a broadcast on its tile and in the summary panel', function () {
    $guild = Guild::factory()->create();
    $creator = User::factory()->create(['name' => 'Ada Admin']);
    $broadcast = Broadcast::factory()->for($guild)->createdBy($creator)->create(['title' => 'Raid Reset']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->assertSee('Created by Ada Admin')
        ->call('select', $broadcast->id)
        ->assertSee('Created by Ada Admin');
});

it('deletes a series with no posted occurrences', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->call('select', $broadcast->id)
        ->call('delete')
        ->assertSet('selectedId', null);

    expect(Broadcast::query()->find($broadcast->id))->toBeNull();
});

it('does not offer delete, and rejects it server-side, once a series has a posted occurrence', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create();
    BroadcastOccurrence::factory()->posted()->create(['broadcast_id' => $broadcast->id]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->call('select', $broadcast->id)
        ->assertDontSeeHtml('wire:click="delete"')
        ->call('delete')
        ->assertHasErrors('delete');

    expect(Broadcast::query()->find($broadcast->id))->not->toBeNull();
});

it('toggles into and out of the edit form for the selected broadcast', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->call('select', $broadcast->id)
        ->assertDontSeeLivewire('broadcasts.edit-broadcast')
        ->call('toggleEdit')
        ->assertSeeLivewire('broadcasts.edit-broadcast')
        ->call('toggleEdit')
        ->assertDontSeeLivewire('broadcasts.edit-broadcast');
});

it('shows the broadcast summary in the detail panel when a tile is selected', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create(['title' => 'Raid Reset', 'message_template' => 'Reset soon!']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->assertSee('Select an item from the list')
        ->call('select', $broadcast->id)
        ->assertDontSee('Select an item from the list')
        ->assertSee('Reset soon!');
});

it('returns to the list-only view on deselect', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->call('select', $broadcast->id)
        ->call('deselect')
        ->assertSee('Select an item from the list');
});

it('refuses to select a broadcast belonging to a different guild', function () {
    $guild = Guild::factory()->create();
    $otherGuild = Guild::factory()->create();
    $otherBroadcast = Broadcast::factory()->for($otherGuild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->call('select', $otherBroadcast->id)
        ->assertSee('Select an item from the list');
});

it('opening the create form deselects the current broadcast, and selecting a tile closes the create form', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    $component = Livewire::actingAs($staff)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->call('select', $broadcast->id)
        ->call('toggleCreateForm');

    $component->assertSet('selectedId', null)
        ->assertSeeLivewire('broadcasts.create-broadcast');

    $component->call('select', $broadcast->id)
        ->assertSet('showCreateForm', false)
        ->assertDontSeeLivewire('broadcasts.create-broadcast');
});

it('selects the newly created broadcast after submitting the create form', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    $component = Livewire::actingAs($staff)->test(BroadcastIndex::class, ['guild' => $guild]);
    $component->call('toggleCreateForm');

    $broadcast = Broadcast::factory()->for($guild)->create();
    $component->dispatch('broadcast-created', broadcastId: $broadcast->id);

    $component->assertSet('showCreateForm', false)
        ->assertSet('selectedId', $broadcast->id);
});

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(BroadcastIndex::class, ['guild' => $guild])
        ->assertForbidden();
});
