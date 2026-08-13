<?php

declare(strict_types=1);

use App\Actions\StandardGiveaways\CreateStandardGiveawayAction;
use App\Models\CollectionTheme;
use App\Models\CollectionThemeItem;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;

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
