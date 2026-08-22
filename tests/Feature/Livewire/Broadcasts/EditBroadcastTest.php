<?php

declare(strict_types=1);

use App\Livewire\Broadcasts\EditBroadcast;
use App\Models\Broadcast;
use App\Models\BroadcastOccurrence;
use App\Models\Guild;
use App\Models\User;
use Livewire\Livewire;

it('pre-fills the form from the existing broadcast', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create([
        'title' => 'Raid Reset',
        'channel_id' => '999888777',
    ]);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditBroadcast::class, ['broadcast' => $broadcast])
        ->assertSet('title', 'Raid Reset')
        ->assertSet('channelId', '999888777')
        ->assertSet('messageTemplate', $broadcast->message_template);
});

it('pre-fills recurrence fields by parsing the stored rrule', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)
        ->recurring('FREQ=WEEKLY;INTERVAL=1;BYDAY=WE', now()->next('Wednesday')->setTime(20, 0), 'UTC')
        ->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EditBroadcast::class, ['broadcast' => $broadcast])
        ->assertSet('recurrenceType', 'weekly')
        ->assertSet('recurrenceDaysOfWeek', ['WE']);
});

it('saves changes to a broadcast', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create(['title' => 'Old Title']);
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(EditBroadcast::class, ['broadcast' => $broadcast])
        ->set('title', 'New Title')
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertDispatched('broadcast-updated')
        ->assertHasNoErrors();

    expect($broadcast->fresh()->title)->toBe('New Title');
});

it('leaves already-generated occurrences unaffected by an edit', function () {
    $guild = Guild::factory()->create();
    $broadcast = Broadcast::factory()->for($guild)->create(['message_template' => 'Old template']);
    $occurrence = BroadcastOccurrence::factory()->fromBroadcast($broadcast)->create();
    $staff = actingEventStaffFor($guild);
    $future = now()->addWeek();

    Livewire::actingAs($staff)
        ->test(EditBroadcast::class, ['broadcast' => $broadcast])
        ->set('messageTemplate', 'New template')
        ->set('startDate', $future->toDateString())
        ->set('startTime', '20:00')
        ->call('save')
        ->assertHasNoErrors();

    expect($occurrence->fresh()->message_template)->toBe('Old template');
});

it('denies mounting for a guild the user does not admin', function () {
    $broadcast = Broadcast::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EditBroadcast::class, ['broadcast' => $broadcast])
        ->assertForbidden();
});
