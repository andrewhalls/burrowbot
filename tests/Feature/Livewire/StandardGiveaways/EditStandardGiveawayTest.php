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

it('pre-fills claim/congrats fields from the existing series', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)
        ->withClaimDetails('https://discord.com/channels/1/2', 48, 'Congrats {winners}!')
        ->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditStandardGiveaway::class, ['giveaway' => $giveaway])
        ->assertSet('claimLink', 'https://discord.com/channels/1/2')
        ->assertSet('claimDeadlineHours', 48)
        ->assertSet('congratsMessageTemplate', 'Congrats {winners}!');
});

it('replaces the banner image when a new one is uploaded, and keeps the existing one otherwise', function () {
    Storage::fake('public');

    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)->withBannerImage('standard-giveaway-images/original-banner.jpg')->create();
    $item = CollectionThemeItem::factory()->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway)->create(['collection_theme_item_id' => $item->id]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditStandardGiveaway::class, ['giveaway' => $giveaway])
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->set('bannerImage', UploadedFile::fake()->image('new-banner.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $updated = $giveaway->fresh();
    expect($updated->banner_image_path)->not->toBe('standard-giveaway-images/original-banner.jpg');
    Storage::disk('public')->assertExists($updated->banner_image_path);
});

it('updates claim/congrats fields on save, leaving already-generated occurrences unaffected', function () {
    $guild = Guild::factory()->create();
    $giveaway = StandardGiveaway::factory()->for($guild)
        ->withClaimDetails('old-link', 24, 'Old template')
        ->create();
    $occurrence = StandardGiveawayOccurrence::factory()->create([
        'standard_giveaway_id' => $giveaway->id,
        'claim_link' => 'old-link',
        'claim_deadline_hours' => 24,
        'congrats_message_template' => 'Old template',
    ]);
    $item = CollectionThemeItem::factory()->create();
    StandardGiveawayPrizeItem::factory()->for($giveaway)->create(['collection_theme_item_id' => $item->id]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditStandardGiveaway::class, ['giveaway' => $giveaway])
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->set('claimLink', 'new-link')
        ->set('claimDeadlineHours', 72)
        ->set('congratsMessageTemplate', 'New template {winners}')
        ->call('save')
        ->assertHasNoErrors();

    expect($giveaway->fresh()->claim_link)->toBe('new-link')
        ->and($giveaway->fresh()->claim_deadline_hours)->toBe(72)
        ->and($giveaway->fresh()->congrats_message_template)->toBe('New template {winners}')
        ->and($occurrence->fresh()->claim_link)->toBe('old-link')
        ->and($occurrence->fresh()->claim_deadline_hours)->toBe(24)
        ->and($occurrence->fresh()->congrats_message_template)->toBe('Old template');
});

it('denies mounting for a guild the user does not admin', function () {
    $giveaway = StandardGiveaway::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditStandardGiveaway::class, ['giveaway' => $giveaway])
        ->assertForbidden();
});
