<?php

declare(strict_types=1);

use App\Livewire\Giveaways\CreateGiveaway;
use App\Models\CollectionTheme;
use App\Models\DiscordChannel;
use App\Models\Giveaway;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('creates a giveaway with valid input', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 15)
        ->call('save')
        ->assertDispatched('giveaway-created')
        ->assertHasNoErrors();

    expect(Giveaway::query()->where('guild_id', $guild->id)->count())->toBe(1);
});

it('creates a giveaway with a description and an image', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 15)
        ->set('description', 'A very special giveaway')
        ->set('image', UploadedFile::fake()->image('prize.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = Giveaway::query()->where('guild_id', $guild->id)->sole();
    expect($giveaway->description)->toBe('A very special giveaway')
        ->and($giveaway->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($giveaway->image_path);
});

it('creates a giveaway with no description or image left unset', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 15)
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = Giveaway::query()->where('guild_id', $guild->id)->sole();
    expect($giveaway->description)->toBeNull()
        ->and($giveaway->image_path)->toBeNull();
});

it('rejects an oversized image upload', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 15)
        ->set('image', UploadedFile::fake()->image('too-big.jpg')->size(6000))
        ->call('save')
        ->assertHasErrors('image');

    expect(Giveaway::query()->count())->toBe(0);
});

it('rejects a non-image upload', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 15)
        ->set('image', UploadedFile::fake()->create('document.pdf', 100))
        ->call('save')
        ->assertHasErrors('image');

    expect(Giveaway::query()->count())->toBe(0);
});

it('rejects a non-positive duration', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 0)
        ->call('save')
        ->assertHasErrors('durationMinutes');

    expect(Giveaway::query()->count())->toBe(0);
});

it('rejects a collection theme belonging to a different guild', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $otherGuildTheme = CollectionTheme::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $otherGuildTheme->id)
        ->set('durationMinutes', 15)
        ->call('save')
        ->assertHasErrors('collectionThemeId');
});

it('shows the channel picker scoped to this guild, not another guild\'s channels', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    DiscordChannel::factory()->for($guild)->create(['name' => 'announcements']);
    $otherGuild = Guild::factory()->create();
    DiscordChannel::factory()->for($otherGuild)->create(['name' => 'other-guild-general']);

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->assertSee('#announcements')
        ->assertDontSee('#other-guild-general');
});

it('shows an empty (not broken) channel picker when the guild has no synced channels', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->assertSee('No synced channels yet.');
});

it('displays the synced channel name in the picker when the field already holds its id', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $channel = DiscordChannel::factory()->for($guild)->create(['name' => 'giveaways']);

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', $channel->discord_channel_id)
        ->assertSee('#giveaways');
});

it('denies mounting for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->assertForbidden();
});

it('creates a giveaway with a future scheduled start', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $future = now()->addDay();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 15)
        ->set('scheduledStartDate', $future->toDateString())
        ->set('scheduledStartTime', $future->format('H:i'))
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = Giveaway::query()->where('guild_id', $guild->id)->sole();
    expect($giveaway->scheduled_start_at)->not->toBeNull()
        ->and($giveaway->isDraft())->toBeTrue();
});

it('converts a scheduled start entered in a non-UTC browser timezone to UTC for storage', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $future = now('America/New_York')->addDay();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 15)
        ->set('browserTimezone', 'America/New_York')
        ->set('scheduledStartDate', $future->toDateString())
        ->set('scheduledStartTime', $future->format('H:i'))
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = Giveaway::query()->where('guild_id', $guild->id)->sole();
    $expectedUtc = Carbon::parse("{$future->toDateString()} {$future->format('H:i')}", 'America/New_York')->utc();
    expect($giveaway->scheduled_start_at->equalTo($expectedUtc))->toBeTrue();
});

it('falls back to UTC when browserTimezone is invalid', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $future = now()->addDay();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 15)
        ->set('browserTimezone', 'Not/AZone')
        ->set('scheduledStartDate', $future->toDateString())
        ->set('scheduledStartTime', $future->format('H:i'))
        ->call('save')
        ->assertHasNoErrors();

    $giveaway = Giveaway::query()->where('guild_id', $guild->id)->sole();
    $expectedUtc = Carbon::parse("{$future->toDateString()} {$future->format('H:i')}", 'UTC');
    expect($giveaway->scheduled_start_at->equalTo($expectedUtc))->toBeTrue();
});

it('rejects a scheduled start in the past', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();
    $theme = CollectionTheme::factory()->for($guild)->create();
    $past = now()->subDay();

    Livewire::actingAs($user)
        ->test(CreateGiveaway::class, ['guild' => $guild])
        ->set('channelId', '123456')
        ->set('collectionThemeId', $theme->id)
        ->set('durationMinutes', 15)
        ->set('scheduledStartDate', $past->toDateString())
        ->set('scheduledStartTime', $past->format('H:i'))
        ->call('save')
        ->assertHasErrors('scheduledStartDate');

    expect(Giveaway::query()->count())->toBe(0);
});
