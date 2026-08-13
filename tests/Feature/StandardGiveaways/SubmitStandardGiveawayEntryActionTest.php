<?php

declare(strict_types=1);

use App\Actions\StandardGiveaways\SubmitStandardGiveawayEntryAction;
use App\Models\DiscordMember;
use App\Models\StandardGiveawayEntry;
use App\Models\StandardGiveawayOccurrence;
use App\Support\StandardGiveaways\StandardGiveawayEntryResult;

function submitEntryAction(): SubmitStandardGiveawayEntryAction
{
    return app(SubmitStandardGiveawayEntryAction::class);
}

it('accepts an entry on an open, unrestricted occurrence', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create(['ends_at' => now()->addDay()]);

    $result = submitEntryAction()->execute($occurrence, '111', 'entrant', [], false);

    expect($result->status)->toBe(StandardGiveawayEntryResult::STATUS_ENTERED)
        ->and(StandardGiveawayEntry::query()->count())->toBe(1);
});

it('is idempotent on a duplicate entry', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create(['ends_at' => now()->addDay()]);

    submitEntryAction()->execute($occurrence, '111', 'entrant', [], false);
    $result = submitEntryAction()->execute($occurrence, '111', 'entrant', [], false);

    expect($result->status)->toBe(StandardGiveawayEntryResult::STATUS_ALREADY_ENTERED)
        ->and(StandardGiveawayEntry::query()->count())->toBe(1);
});

it('rejects a non-booster on a booster-only occurrence', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create([
        'requires_booster' => true,
        'ends_at' => now()->addDay(),
    ]);

    $result = submitEntryAction()->execute($occurrence, '111', 'entrant', [], false);

    expect($result->status)->toBe(StandardGiveawayEntryResult::STATUS_REJECTED)
        ->and($result->reason)->not->toBeNull()
        ->and(StandardGiveawayEntry::query()->count())->toBe(0);
});

it('accepts a booster on a booster-only occurrence', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create([
        'requires_booster' => true,
        'ends_at' => now()->addDay(),
    ]);

    $result = submitEntryAction()->execute($occurrence, '111', 'entrant', [], true);

    expect($result->status)->toBe(StandardGiveawayEntryResult::STATUS_ENTERED);
});

it('rejects a member without any required role', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create([
        'required_role_ids' => ['role-a', 'role-b'],
        'ends_at' => now()->addDay(),
    ]);

    $result = submitEntryAction()->execute($occurrence, '111', 'entrant', ['role-c'], false);

    expect($result->status)->toBe(StandardGiveawayEntryResult::STATUS_REJECTED)
        ->and(StandardGiveawayEntry::query()->count())->toBe(0);
});

it('accepts a member holding at least one required role', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create([
        'required_role_ids' => ['role-a', 'role-b'],
        'ends_at' => now()->addDay(),
    ]);

    $result = submitEntryAction()->execute($occurrence, '111', 'entrant', ['role-b', 'role-z'], false);

    expect($result->status)->toBe(StandardGiveawayEntryResult::STATUS_ENTERED);
});

it('requires both booster and role when both are configured', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create([
        'requires_booster' => true,
        'required_role_ids' => ['role-a'],
        'ends_at' => now()->addDay(),
    ]);

    $boosterOnlyNoRole = submitEntryAction()->execute($occurrence, '111', 'a', [], true);
    $roleOnlyNoBooster = submitEntryAction()->execute($occurrence, '222', 'b', ['role-a'], false);
    $both = submitEntryAction()->execute($occurrence, '333', 'c', ['role-a'], true);

    expect($boosterOnlyNoRole->status)->toBe(StandardGiveawayEntryResult::STATUS_REJECTED)
        ->and($roleOnlyNoBooster->status)->toBe(StandardGiveawayEntryResult::STATUS_REJECTED)
        ->and($both->status)->toBe(StandardGiveawayEntryResult::STATUS_ENTERED);
});

it('rejects an entry after the occurrence has ended, even if status has not flipped yet', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create(['ends_at' => now()->subMinute()]);

    $result = submitEntryAction()->execute($occurrence, '111', 'entrant', [], false);

    expect($result->status)->toBe(StandardGiveawayEntryResult::STATUS_CLOSED)
        ->and(StandardGiveawayEntry::query()->count())->toBe(0);
});

it('creates the discord member record if it does not already exist', function () {
    $occurrence = StandardGiveawayOccurrence::factory()->posted()->create(['ends_at' => now()->addDay()]);

    submitEntryAction()->execute($occurrence, '999', 'newbie', [], false);

    expect(DiscordMember::query()->where('discord_user_id', '999')->where('username', 'newbie')->exists())->toBeTrue();
});
