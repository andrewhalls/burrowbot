<?php

declare(strict_types=1);

use App\Livewire\Events\CreateEvent;
use App\Models\DiscordChannel;
use App\Models\Event;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Models\GuildAdmin;
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
        ->test(CreateEvent::class, ['guild' => $guild])
        ->assertSee('#announcements')
        ->assertDontSee('#other-guild-general');
});

it('shows an empty (not broken) channel picker when the guild has no synced channels', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->assertSee('No synced channels yet.');
});

it('creates an event with an image', function () {
    Storage::fake('public');

    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->set('title', 'Game Night')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('eventRoleSetId', $roleSet->id)
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->set('image', UploadedFile::fake()->image('event.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::query()->where('title', 'Game Night')->sole();
    expect($event->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($event->image_path);
});

it('records the authenticated admin as the creator', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->set('title', 'Game Night')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('eventRoleSetId', $roleSet->id)
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::query()->where('title', 'Game Night')->sole();
    expect($event->created_by_user_id)->toBe($staff->id);
});

it('records the browser timezone alongside the wall-clock start time, unconverted', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->set('title', 'Timezone Test')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('eventRoleSetId', $roleSet->id)
        ->set('browserTimezone', 'America/New_York')
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::query()->where('title', 'Timezone Test')->sole();
    expect($event->recurrence_timezone)->toBe('America/New_York')
        ->and($event->recurrence_start_at->format('Y-m-d H:i'))->toBe("{$future->toDateString()} 20:00");
});

it('falls back to UTC when browserTimezone is invalid', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->set('title', 'Invalid TZ Test')
        ->set('description', 'desc')
        ->set('channelId', '123456')
        ->set('eventRoleSetId', $roleSet->id)
        ->set('browserTimezone', 'Not/AZone')
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::query()->where('title', 'Invalid TZ Test')->sole();
    expect($event->recurrence_timezone)->toBe('UTC');
});

it('creates a one-off event', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->set('title', 'Game Night')
        ->set('description', 'Bring snacks')
        ->set('channelId', '123456')
        ->set('eventRoleSetId', $roleSet->id)
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertDispatched('event-created')
        ->assertHasNoErrors();

    $event = Event::query()->where('title', 'Game Night')->first();
    expect($event)->not->toBeNull()
        ->and($event->occurrences)->toHaveCount(1);
});

it('creates a weekly recurring event with no occurrences yet', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->set('title', 'Raid Night')
        ->set('description', 'Weekly raid')
        ->set('channelId', '123456')
        ->set('eventRoleSetId', $roleSet->id)
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->set('recurrenceType', 'weekly')
        ->set('recurrenceDaysOfWeek', ['WE'])
        ->call('save')
        ->assertHasNoErrors();

    $event = Event::query()->where('title', 'Raid Night')->first();
    expect($event->isRecurring())->toBeTrue()
        ->and($event->occurrences)->toHaveCount(0);
});

it('rejects a weekly recurrence with no days selected', function () {
    $guild = Guild::factory()->create();
    $roleSet = EventRoleSet::factory()->for($guild)->withRoles(1)->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->set('title', 'Raid Night')
        ->set('description', 'Weekly raid')
        ->set('channelId', '123456')
        ->set('eventRoleSetId', $roleSet->id)
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->set('recurrenceType', 'weekly')
        ->call('save')
        ->assertHasErrors('recurrenceType');

    expect(Event::query()->count())->toBe(0);
});

it('denies mounting the component for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateEvent::class, ['guild' => $guild])
        ->assertForbidden();
});
