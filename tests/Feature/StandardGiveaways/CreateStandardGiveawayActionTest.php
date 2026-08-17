<?php

declare(strict_types=1);

use App\Actions\StandardGiveaways\CreateStandardGiveawayAction;
use App\Models\CollectionTheme;
use App\Models\CollectionThemeItem;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use App\Models\User;

it('creates a one-off giveaway with a single occurrence immediately', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();
    $startAt = now()->addWeek();

    $giveaway = (new CreateStandardGiveawayAction)->execute(
        $guild, 'Nitro Friday', 'One lucky booster', '12345', StandardGiveaway::POSTING_MODE_MESSAGE,
        1, true, 10080, [$item->id], [],
        null, $startAt, 'UTC',
    );

    expect($giveaway->isRecurring())->toBeFalse()
        ->and($giveaway->prizeItems)->toHaveCount(1)
        ->and($giveaway->occurrences)->toHaveCount(1);

    $occurrence = $giveaway->occurrences->first();
    expect($occurrence->prize_item_ids)->toBe([$item->id])
        ->and($occurrence->requires_booster)->toBeTrue()
        ->and($occurrence->status)->toBe(StandardGiveawayOccurrence::STATUS_SCHEDULED);
});

it('creates a recurring giveaway with no occurrences yet', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();

    $giveaway = (new CreateStandardGiveawayAction)->execute(
        $guild, 'Weekly Draw', 'Every Friday', '12345', StandardGiveaway::POSTING_MODE_MESSAGE,
        1, false, 4320, [$item->id], [],
        'FREQ=WEEKLY;BYDAY=FR', now()->addWeek(), 'UTC',
    );

    expect($giveaway->isRecurring())->toBeTrue()
        ->and($giveaway->occurrences)->toHaveCount(0);
});

it('stores required role ids', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();

    $giveaway = (new CreateStandardGiveawayAction)->execute(
        $guild, 'Booster Role Giveaway', 'desc', '12345', StandardGiveaway::POSTING_MODE_MESSAGE,
        1, false, 60, [$item->id], ['111', '222'],
        null, now()->addDay(), 'UTC',
    );

    expect($giveaway->requiredRoles->pluck('discord_role_id')->all())->toBe(['111', '222']);
});

it('records the creator when provided', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();
    $user = User::factory()->create();

    $giveaway = (new CreateStandardGiveawayAction)->execute(
        $guild, 'Nitro Friday', 'One lucky booster', '12345', StandardGiveaway::POSTING_MODE_MESSAGE,
        1, true, 10080, [$item->id], [],
        null, now()->addWeek(), 'UTC', createdBy: $user,
    );

    expect($giveaway->created_by_user_id)->toBe($user->id);
});

it('leaves the creator null when not provided', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();

    $giveaway = (new CreateStandardGiveawayAction)->execute(
        $guild, 'Nitro Friday', 'One lucky booster', '12345', StandardGiveaway::POSTING_MODE_MESSAGE,
        1, true, 10080, [$item->id], [],
        null, now()->addWeek(), 'UTC',
    );

    expect($giveaway->created_by_user_id)->toBeNull();
});

it('persists banner image and claim/congrats fields, snapshotting them onto the immediate occurrence', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();

    $giveaway = (new CreateStandardGiveawayAction)->execute(
        $guild, 'Booster Giveaway', 'desc', '12345', StandardGiveaway::POSTING_MODE_MESSAGE,
        1, true, 60, [$item->id], [],
        null, now()->addDay(), 'UTC',
        bannerImagePath: 'standard-giveaway-images/banner.jpg',
        claimLink: 'https://discord.com/channels/1/2',
        claimDeadlineHours: 48,
        congratsMessageTemplate: 'Congrats {winners}! You won {prize}.',
    );

    expect($giveaway->banner_image_path)->toBe('standard-giveaway-images/banner.jpg')
        ->and($giveaway->claim_link)->toBe('https://discord.com/channels/1/2')
        ->and($giveaway->claim_deadline_hours)->toBe(48)
        ->and($giveaway->congrats_message_template)->toBe('Congrats {winners}! You won {prize}.');

    $occurrence = $giveaway->occurrences->first();
    expect($occurrence->banner_image_path)->toBe('standard-giveaway-images/banner.jpg')
        ->and($occurrence->claim_link)->toBe('https://discord.com/channels/1/2')
        ->and($occurrence->claim_deadline_hours)->toBe(48)
        ->and($occurrence->congrats_message_template)->toBe('Congrats {winners}! You won {prize}.');
});

it('leaves banner image and claim/congrats fields null when not provided', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();

    $giveaway = (new CreateStandardGiveawayAction)->execute(
        $guild, 'Plain Giveaway', 'desc', '12345', StandardGiveaway::POSTING_MODE_MESSAGE,
        1, false, 60, [$item->id], [],
        null, now()->addDay(), 'UTC',
    );

    expect($giveaway->banner_image_path)->toBeNull()
        ->and($giveaway->claim_link)->toBeNull()
        ->and($giveaway->claim_deadline_hours)->toBeNull()
        ->and($giveaway->congrats_message_template)->toBeNull();
});

it('stores the one-off occurrence\'s scheduled_post_at as a true UTC instant, not the local timezone\'s wall-clock digits', function () {
    $guild = Guild::factory()->create();
    $theme = CollectionTheme::factory()->for($guild)->withItems(1)->create();
    $item = $theme->items->first();
    $localStart = now('Asia/Tokyo')->addDay()->setTime(18, 0);

    $giveaway = (new CreateStandardGiveawayAction)->execute(
        $guild, 'Nitro Friday', 'desc', '12345', StandardGiveaway::POSTING_MODE_MESSAGE,
        1, false, 60, [$item->id], [],
        null, $localStart, 'Asia/Tokyo',
    );

    $occurrence = $giveaway->occurrences->first();

    // 18:00 in Asia/Tokyo (UTC+9, no DST) is 09:00 UTC - if scheduled_post_at
    // were wrongly stored as the wall-clock "18:00" digits instead of the
    // converted UTC instant, this would read "18:00" instead.
    expect($occurrence->scheduled_post_at->clone()->utc()->format('H:i'))->toBe('09:00');
});

it('rejects zero prize items and creates nothing', function () {
    $guild = Guild::factory()->create();

    expect(fn () => (new CreateStandardGiveawayAction)->execute(
        $guild, 'Empty', 'desc', '12345', StandardGiveaway::POSTING_MODE_MESSAGE,
        1, false, 60, [], [],
        null, now()->addDay(), 'UTC',
    ))->toThrow(InvalidArgumentException::class);

    expect(StandardGiveaway::query()->count())->toBe(0);
});

it('rejects a prize item belonging to a different guild', function () {
    $guild = Guild::factory()->create();
    $otherGuildItem = CollectionThemeItem::factory()->create(); // different theme/guild by default

    expect(fn () => (new CreateStandardGiveawayAction)->execute(
        $guild, 'Cross Guild', 'desc', '12345', StandardGiveaway::POSTING_MODE_MESSAGE,
        1, false, 60, [$otherGuildItem->id], [],
        null, now()->addDay(), 'UTC',
    ))->toThrow(InvalidArgumentException::class);

    expect(StandardGiveaway::query()->count())->toBe(0);
});
