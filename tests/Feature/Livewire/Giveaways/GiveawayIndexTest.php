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

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(GiveawayIndex::class, ['guild' => $guild])
        ->assertForbidden();
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
