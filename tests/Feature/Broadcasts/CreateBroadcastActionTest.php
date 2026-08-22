<?php

declare(strict_types=1);

use App\Actions\Broadcasts\CreateBroadcastAction;
use App\Models\BroadcastOccurrence;
use App\Models\Guild;
use App\Models\User;
use Illuminate\Support\Carbon;

it('creates a one-off broadcast with a single occurrence immediately', function () {
    $guild = Guild::factory()->create();
    $startAt = now()->addWeek();

    $broadcast = (new CreateBroadcastAction)->execute(
        $guild, 'Raid Reset', 'Reset is at {{time}} in {{channel}}.', '12345',
        null, $startAt, 'UTC',
    );

    expect($broadcast->isRecurring())->toBeFalse()
        ->and($broadcast->occurrences)->toHaveCount(1);

    $occurrence = $broadcast->occurrences->first();
    expect($occurrence->message_template)->toBe('Reset is at {{time}} in {{channel}}.')
        ->and($occurrence->channel_id)->toBe('12345')
        ->and($occurrence->scheduled_post_at->timestamp)->toBe($startAt->timestamp)
        ->and($occurrence->status)->toBe(BroadcastOccurrence::STATUS_SCHEDULED);
});

it('stores the one-off occurrence\'s scheduled_post_at as a true UTC instant, not reinterpreted wall-clock', function () {
    $guild = Guild::factory()->create();

    $localStartAt = Carbon::parse(now()->addWeek()->toDateString().' 20:00', 'America/New_York');

    $broadcast = (new CreateBroadcastAction)->execute(
        $guild, 'Raid Reset', 'Reset time!', '12345',
        null, $localStartAt, 'America/New_York',
    );

    $occurrence = $broadcast->occurrences->first();

    expect($occurrence->scheduled_post_at->timestamp)->toBe($localStartAt->clone()->utc()->timestamp);
});

it('records the creator when provided', function () {
    $guild = Guild::factory()->create();
    $user = User::factory()->create();

    $broadcast = (new CreateBroadcastAction)->execute(
        $guild, 'Raid Reset', 'Reset time!', '12345',
        null, now()->addWeek(), 'UTC', createdBy: $user,
    );

    expect($broadcast->created_by_user_id)->toBe($user->id);
});

it('leaves the creator null when not provided', function () {
    $guild = Guild::factory()->create();

    $broadcast = (new CreateBroadcastAction)->execute(
        $guild, 'Raid Reset', 'Reset time!', '12345',
        null, now()->addWeek(), 'UTC',
    );

    expect($broadcast->created_by_user_id)->toBeNull();
});

it('creates a recurring broadcast with no occurrences yet', function () {
    $guild = Guild::factory()->create();

    $broadcast = (new CreateBroadcastAction)->execute(
        $guild, 'Weekly Reminder', 'Reset time!', '12345',
        'FREQ=WEEKLY;BYDAY=WE', now()->addWeek(), 'UTC',
    );

    expect($broadcast->isRecurring())->toBeTrue()
        ->and($broadcast->occurrences)->toHaveCount(0);
});
