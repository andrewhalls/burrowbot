<?php

declare(strict_types=1);

use App\Livewire\Broadcasts\CreateBroadcast;
use App\Models\Broadcast;
use App\Models\DiscordChannel;
use App\Models\Guild;
use App\Models\User;
use Livewire\Livewire;

it('shows the channel picker scoped to this guild, not another guild\'s channels', function () {
    $guild = Guild::factory()->create();
    DiscordChannel::factory()->for($guild)->create(['name' => 'announcements']);
    $otherGuild = Guild::factory()->create();
    DiscordChannel::factory()->for($otherGuild)->create(['name' => 'other-guild-general']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateBroadcast::class, ['guild' => $guild])
        ->assertSee('#announcements')
        ->assertDontSee('#other-guild-general');
});

it('records the authenticated admin as the creator', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(CreateBroadcast::class, ['guild' => $guild])
        ->set('title', 'Raid Reset')
        ->set('messageTemplate', 'Reset is at {{time}}.')
        ->set('channelId', '123456')
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertHasNoErrors();

    $broadcast = Broadcast::query()->where('title', 'Raid Reset')->sole();
    expect($broadcast->created_by_user_id)->toBe($staff->id);
});

it('creates a one-off broadcast', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateBroadcast::class, ['guild' => $guild])
        ->set('title', 'Raid Reset')
        ->set('messageTemplate', 'Reset is at {{time}}.')
        ->set('channelId', '123456')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertDispatched('broadcast-created')
        ->assertHasNoErrors();

    $broadcast = Broadcast::query()->where('title', 'Raid Reset')->first();
    expect($broadcast)->not->toBeNull()
        ->and($broadcast->occurrences)->toHaveCount(1);
});

it('creates a weekly recurring broadcast with no occurrences yet', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateBroadcast::class, ['guild' => $guild])
        ->set('title', 'Weekly Reminder')
        ->set('messageTemplate', 'Weekly reminder!')
        ->set('channelId', '123456')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->set('recurrenceType', 'weekly')
        ->set('recurrenceDaysOfWeek', ['WE'])
        ->call('save')
        ->assertHasNoErrors();

    $broadcast = Broadcast::query()->where('title', 'Weekly Reminder')->first();
    expect($broadcast->isRecurring())->toBeTrue()
        ->and($broadcast->occurrences)->toHaveCount(0);
});

it('rejects a weekly recurrence with no days selected', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(CreateBroadcast::class, ['guild' => $guild])
        ->set('title', 'Weekly Reminder')
        ->set('messageTemplate', 'Weekly reminder!')
        ->set('channelId', '123456')
        ->set('startDate', now()->addWeek()->toDateString())
        ->set('startTime', '20:00')
        ->set('recurrenceType', 'weekly')
        ->call('save')
        ->assertHasErrors('recurrenceType');

    expect(Broadcast::query()->count())->toBe(0);
});

it('denies mounting the component for a guild the user does not admin', function () {
    $user = User::factory()->create();
    $guild = Guild::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateBroadcast::class, ['guild' => $guild])
        ->assertForbidden();
});
