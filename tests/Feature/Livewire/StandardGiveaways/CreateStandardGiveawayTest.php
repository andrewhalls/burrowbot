<?php

declare(strict_types=1);

use App\Livewire\StandardGiveaways\CreateStandardGiveaway;
use App\Models\CollectionTheme;
use App\Models\DiscordChannel;
use App\Models\DiscordRole;
use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayRequiredRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('shows the channel picker scoped to this guild, not another guild\'s channels', function () {
    $guild = Guild::factory()->create();
    DiscordChannel::factory()->for($guild)->create(['name' => 'announcements']);
    $otherGuild = Guild::factory()->create();
    DiscordChannel::factory()->for($otherGuild)->create(['name' => 'other-guild-general']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->assertSee('#announcements')
        ->assertDontSee('#other-guild-general');
});

it('shows an empty (not broken) channel picker when the guild has no synced channels', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->assertSee('No synced channels yet.');
});

it('adds a role to the selection via search, scoped to the guild', function () {
    $guild = Guild::factory()->create();
    $role = DiscordRole::factory()->for($guild)->create(['name' => 'Officer']);
    $otherGuild = Guild::factory()->create();
    DiscordRole::factory()->for($otherGuild)->create(['name' => 'OtherOfficer']);
    $staff = actingEventStaffFor($guild);

    $component = Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('roleSearch', 'Officer')
        ->assertSee('Officer')
        ->assertDontSee('OtherOfficer')
        ->call('addDiscordRole', $role->discord_role_id);

    expect($component->get('selectedRoleIds'))->toBe([$role->discord_role_id]);
});

it('removes a role chip from the selection', function () {
    $guild = Guild::factory()->create();
    $role = DiscordRole::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    $component = Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->call('addDiscordRole', $role->discord_role_id)
        ->call('removeDiscordRole', $role->discord_role_id);

    expect($component->get('selectedRoleIds'))->toBe([]);
});

it('bulk-adds roles from an event role set preset, de-duplicated against the current selection', function () {
    $guild = Guild::factory()->create();
    $roleA = DiscordRole::factory()->for($guild)->create();
    $roleB = DiscordRole::factory()->for($guild)->create();
    $roleSet = EventRoleSet::factory()->for($guild)->create();
    EventRole::factory()->for($roleSet, 'eventRoleSet')->withDiscordRoleId($roleA->discord_role_id)->create();
    EventRole::factory()->for($roleSet, 'eventRoleSet')->withDiscordRoleId($roleB->discord_role_id)->create();
    $staff = actingEventStaffFor($guild);

    $component = Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->call('addDiscordRole', $roleA->discord_role_id)
        ->call('addRoleSetPreset', $roleSet->id);

    expect($component->get('selectedRoleIds'))->toEqualCanonicalizing([$roleA->discord_role_id, $roleB->discord_role_id]);
});

it('persists the selected roles as StandardGiveawayRequiredRole rows on save', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();
    $role = DiscordRole::factory()->for($guild)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('title', 'Role Restricted')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('addPrizeItem', $item->id)
        ->call('addDiscordRole', $role->discord_role_id)
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = StandardGiveaway::query()->where('title', 'Role Restricted')->sole();
    expect(StandardGiveawayRequiredRole::query()->where('standard_giveaway_id', $giveaway->id)->pluck('discord_role_id')->all())
        ->toBe([$role->discord_role_id]);
});

it('records the authenticated admin as the creator', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
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
        ->assertHasNoErrors();

    $giveaway = StandardGiveaway::query()->where('title', 'Nitro Friday')->sole();
    expect($giveaway->created_by_user_id)->toBe($staff->id);
});

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

it('records the browser timezone alongside the wall-clock start time, unconverted', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('title', 'Timezone Test')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('browserTimezone', 'America/New_York')
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->call('addPrizeItem', $item->id)
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = StandardGiveaway::query()->where('title', 'Timezone Test')->sole();
    expect($giveaway->recurrence_timezone)->toBe('America/New_York')
        ->and($giveaway->recurrence_start_at->format('Y-m-d H:i'))->toBe("{$future->toDateString()} 20:00");
});

it('falls back to UTC when browserTimezone is invalid', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('title', 'Invalid TZ Test')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('browserTimezone', 'Not/AZone')
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->call('addPrizeItem', $item->id)
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = StandardGiveaway::query()->where('title', 'Invalid TZ Test')->sole();
    expect($giveaway->recurrence_timezone)->toBe('UTC');
});

it('creates a standard giveaway with an image', function () {
    Storage::fake('public');

    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('title', 'Nitro Friday')
        ->set('description', 'One lucky booster')
        ->set('channelId', '123456')
        ->set('image', UploadedFile::fake()->image('prize.jpg'))
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('addPrizeItem', $item->id)
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = StandardGiveaway::query()->where('title', 'Nitro Friday')->sole();
    expect($giveaway->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($giveaway->image_path);

    $occurrence = $giveaway->occurrences->sole();
    expect($occurrence->image_path)->toBe($giveaway->image_path);
});

it('creates a standard giveaway with a banner image and claim/congrats fields', function () {
    Storage::fake('public');

    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('title', 'Booster Giveaway')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('bannerImage', UploadedFile::fake()->image('banner.jpg'))
        ->set('claimLink', 'https://discord.com/channels/1/2')
        ->set('claimDeadlineHours', 48)
        ->set('congratsMessageTemplate', 'Congrats {winners}! You won {prize}.')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('addPrizeItem', $item->id)
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = StandardGiveaway::query()->where('title', 'Booster Giveaway')->sole();
    expect($giveaway->banner_image_path)->not->toBeNull()
        ->and($giveaway->claim_link)->toBe('https://discord.com/channels/1/2')
        ->and($giveaway->claim_deadline_hours)->toBe(48)
        ->and($giveaway->congrats_message_template)->toBe('Congrats {winners}! You won {prize}.');
    Storage::disk('public')->assertExists($giveaway->banner_image_path);
});

it('creates a standard giveaway with per-winner message fields set, independent of the congrats fields', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('title', 'Booster Giveaway')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('perWinnerMessageChannelId', '987654')
        ->set('perWinnerMessageTemplate', 'Congrats {winner}! You won {prize}.')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('addPrizeItem', $item->id)
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = StandardGiveaway::query()->where('title', 'Booster Giveaway')->sole();
    expect($giveaway->per_winner_message_channel_id)->toBe('987654')
        ->and($giveaway->per_winner_message_template)->toBe('Congrats {winner}! You won {prize}.')
        ->and($giveaway->congrats_message_template)->toBeNull();
});

it('rejects setting only one of the per-winner message fields', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('title', 'Booster Giveaway')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('perWinnerMessageChannelId', '987654')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('addPrizeItem', $item->id)
        ->call('save')
        ->assertHasErrors('perWinnerMessageTemplate');

    expect(StandardGiveaway::query()->where('title', 'Booster Giveaway')->count())->toBe(0);
});

it('leaves banner image and claim/congrats fields null when left blank', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->set('title', 'Plain Giveaway')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('addPrizeItem', $item->id)
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = StandardGiveaway::query()->where('title', 'Plain Giveaway')->sole();
    expect($giveaway->banner_image_path)->toBeNull()
        ->and($giveaway->claim_link)->toBeNull()
        ->and($giveaway->claim_deadline_hours)->toBeNull()
        ->and($giveaway->congrats_message_template)->toBeNull()
        ->and($giveaway->per_winner_message_channel_id)->toBeNull()
        ->and($giveaway->per_winner_message_template)->toBeNull();
});

it('shows the selected prize item\'s name (not a bare id) as a chip', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(0)->create();
    $item = $theme->items()->create(['name' => 'Golden Ticket', 'sort_order' => 0]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateStandardGiveaway::class, ['guild' => $guild])
        ->call('addPrizeItem', $item->id)
        ->assertSee('Golden Ticket')
        ->assertDontSee("#{$item->id}");
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
