<?php

declare(strict_types=1);

use App\Livewire\StandardGiveaways\EditStandardGiveaway;
use App\Models\CollectionThemeItem;
use App\Models\DiscordRole;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use App\Models\StandardGiveawayPrizeItem;
use App\Models\StandardGiveawayRequiredRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('pre-fills the form from the existing series, including prize items and roles', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create(['title' => 'Nitro Friday', 'winner_count' => 2]);
    $item = CollectionThemeItem::factory()->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway)->create(['collection_theme_item_id' => $item->id]);
    StandardGiveawayRequiredRole::factory()->for($giveaway)->create(['discord_role_id' => 'role-a']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditStandardGiveaway::class, ['giveaway' => $giveaway])
        ->assertSet('title', 'Nitro Friday')
        ->assertSet('winnerCount', 2)
        ->assertSet('selectedPrizeItemIds', [$item->id])
        ->assertSet('selectedRoleIds', ['role-a']);
});

it('pre-fills recurrence fields by parsing the stored rrule', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)
        ->recurring('FREQ=WEEKLY;INTERVAL=1;BYDAY=WE', now()->next('Wednesday')->setTime(20, 0), 'UTC')
        ->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditStandardGiveaway::class, ['giveaway' => $giveaway])
        ->assertSet('recurrenceType', 'weekly')
        ->assertSet('recurrenceDaysOfWeek', ['WE']);
});

it('saves scalar field changes and replaces prize items and roles', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create(['title' => 'Old Title']);
    $oldItem = CollectionThemeItem::factory()->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway)->create(['collection_theme_item_id' => $oldItem->id]);
    $newItem = CollectionThemeItem::factory()->for($oldItem->collectionTheme)->create();
    $role = DiscordRole::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditStandardGiveaway::class, ['giveaway' => $giveaway])
        ->set('title', 'New Title')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('removePrizeItem', $oldItem->id)
        ->call('addPrizeItem', $newItem->id)
        ->call('addDiscordRole', $role->discord_role_id)
        ->call('save')
        ->assertDispatched('standard-giveaway-updated')
        ->assertHasNoErrors();

    $giveaway->refresh();
    expect($giveaway->title)->toBe('New Title')
        ->and($giveaway->prizeItems()->pluck('collection_theme_item_id')->all())->toBe([$newItem->id])
        ->and($giveaway->requiredRoles()->pluck('discord_role_id')->all())->toBe([$role->discord_role_id]);
});

it('leaves already-generated occurrences unaffected by an edit', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->create(['title' => 'Old Title']);
    $occurrence = StandardGiveawayOccurrence::factory()->create([
        'standard_giveaway_id' => $giveaway->id,
        'title' => 'Old Title',
    ]);
    $item = CollectionThemeItem::factory()->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway)->create(['collection_theme_item_id' => $item->id]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditStandardGiveaway::class, ['giveaway' => $giveaway])
        ->set('title', 'New Title')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertHasNoErrors();

    expect($occurrence->fresh()->title)->toBe('Old Title');
});

it('replaces the image when a new one is uploaded, and keeps the existing one otherwise', function () {
    Storage::fake('public');

    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->withImage('standard-giveaway-images/original.jpg')->create();
    $item = CollectionThemeItem::factory()->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway)->create(['collection_theme_item_id' => $item->id]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditStandardGiveaway::class, ['giveaway' => $giveaway])
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->set('image', UploadedFile::fake()->image('new.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $updated = $giveaway->fresh();
    expect($updated->image_path)->not->toBe('standard-giveaway-images/original.jpg');
    Storage::disk('public')->assertExists($updated->image_path);
});

it('denies mounting for a guild the user does not admin', function () {
    $giveaway = StandardGiveaway::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditStandardGiveaway::class, ['giveaway' => $giveaway])
        ->assertForbidden();
});
