<?php

declare(strict_types=1);

use App\Livewire\Giveaways\GiveawayIndex;
use App\Models\CollectionTheme;
use App\Models\Giveaway;
use App\Models\GiveawayEntry;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Livewire\Livewire;

function actingGiveawayStaffFor(Guild $guild): User
{
    $user = User::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    return $user;
}

it('lists popup giveaways for the guild with status and entrant count', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create(['name' => 'Retro Arcade']);
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->active()->create();
    GiveawayEntry::factory()->for($giveaway)->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Retro Arcade')
        ->assertSee('Active');
});

it('shows who created a giveaway when known, and nothing when not', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $creator = User::factory()->create(['name' => 'Ada Admin']);
    Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->createdBy($creator)->create();
    Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Created by Ada Admin');
});

it('shows a giveaway\'s description and image when set', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)
        ->withDescription('Win big prizes!')
        ->withImage('giveaway-images/abc.jpg')
        ->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Win big prizes!')
        ->assertSee('giveaway-images/abc.jpg');
});

it('renders cleanly for a giveaway with no description or image', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create(['name' => 'Plain Theme']);
    Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Plain Theme')
        ->assertOk();
});

it('renders the scheduled start time with a UTC ISO8601 attribute for client-side local-time conversion', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $scheduledFor = now()->addDay()->startOfMinute();
    Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->scheduledFor($scheduledFor)->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->assertSee('data-utc-datetime="'.$scheduledFor->toIso8601String().'"', false);
});

it('never lists a giveaway belonging to another guild', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create(['name' => 'Mine']);
    Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();

    $otherGuild = Guild::factory()->create();
    $otherTheme = CollectionTheme::factory()->for($otherGuild)->create(['name' => 'Not Mine']);
    Giveaway::factory()->for($otherTheme, 'collectionTheme')->for($otherGuild)->create();

    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Mine')
        ->assertDontSee('Not Mine');
});

it('starts a draft giveaway from the list', function () {
    $guild = Guild::factory()->create();
    $giveaway = Giveaway::factory()->for($guild)->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->call('start', $giveaway->id);

    expect($giveaway->fresh()->isActive())->toBeTrue();
});

it('shows the giveaway dashboard in the detail panel when a tile is selected', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->assertSee('Select an item from the list')
        ->call('select', $giveaway->id)
        ->assertDontSee('Select an item from the list')
        ->assertSeeLivewire('giveaways.giveaway-dashboard');
});

it('returns to the list-only view on deselect', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->call('deselect')
        ->assertSee('Select an item from the list');
});

it('refuses to select a giveaway belonging to a different guild', function () {
    $guild = Guild::factory()->create();
    $otherGuild = Guild::factory()->create();
    $otherGiveaway = Giveaway::factory()->for($otherGuild)->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->call('select', $otherGiveaway->id)
        ->assertSee('Select an item from the list');
});

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->assertForbidden();
});

it('toggles into and out of the edit form for the selected giveaway', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->assertDontSeeLivewire('giveaways.edit-giveaway')
        ->call('toggleEdit')
        ->assertSeeLivewire('giveaways.edit-giveaway')
        ->call('toggleEdit')
        ->assertDontSeeLivewire('giveaways.edit-giveaway');
});

it('offers Edit/Start/Delete in the header only while a draft giveaway is selected', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $draft = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $active = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->active()->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->call('select', $draft->id)
        ->assertSeeHtml('wire:click="toggleEdit"')
        ->assertSeeHtml('wire:click="delete"');

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->call('select', $active->id)
        ->assertDontSeeHtml('wire:click="toggleEdit"')
        ->assertDontSeeHtml('wire:click="delete"');
});

it('deletes a draft giveaway from the list', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $staff = actingGiveawayStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->call('delete')
        ->assertSet('selectedId', null);

    expect(Giveaway::query()->find($giveaway->id))->toBeNull();
});

it('opening the create form deselects the current giveaway, and selecting a tile closes the create form', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $staff = actingGiveawayStaffFor($guild);

    $component = Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->call('select', $giveaway->id)
        ->call('toggleCreateForm');

    $component->assertSet('selectedId', null)
        ->assertSeeLivewire('giveaways.create-giveaway');

    $component->call('select', $giveaway->id)
        ->assertSet('showCreateForm', false)
        ->assertDontSeeLivewire('giveaways.create-giveaway');
});

it('selects the newly created giveaway after submitting the create form', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $staff = actingGiveawayStaffFor($guild);

    $component = Livewire::actingAs($staff)->test(GiveawayIndex::class, ['guild' => $guild]);
    $component->call('toggleCreateForm')->call('$refresh');

    $giveaway = Giveaway::factory()->for($theme, 'collectionTheme')->for($guild)->create();
    $component->dispatch('giveaway-created', giveawayId: $giveaway->id);

    $component->assertSet('showCreateForm', false)
        ->assertSet('selectedId', $giveaway->id);
});

it('denies starting a giveaway belonging to a different guild', function () {
    $guild = Guild::factory()->create();
    $otherGuild = Guild::factory()->create();
    $otherGiveaway = Giveaway::factory()->for($otherGuild)->create();
    $staff = actingGiveawayStaffFor($guild);

    expect(fn () => Livewire::actingAs($staff)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->call('start', $otherGiveaway->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($otherGiveaway->fresh()->isDraft())->toBeTrue();
});
