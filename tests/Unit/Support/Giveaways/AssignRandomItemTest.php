<?php

declare(strict_types=1);

use App\Support\Giveaways\AssignRandomItem;

it('draws from the unwon pool when items remain unwon', function () {
    $assign = new AssignRandomItem;

    // Deterministic "randomizer": always picks the last index.
    $result = $assign([10, 20, 30], [10, 20, 30, 40], fn (int $min, int $max) => $max);

    expect($result)->toBe(30)
        ->and([10, 20, 30])->toContain($result);
});

it('never returns an already-won item while unwon items remain', function () {
    $assign = new AssignRandomItem;
    $allItemIds = [1, 2, 3, 4, 5];
    $unwonItemIds = [3, 5];

    foreach (range(0, count($unwonItemIds) - 1) as $forcedIndex) {
        $result = $assign($unwonItemIds, $allItemIds, fn (int $min, int $max) => $forcedIndex);
        expect($unwonItemIds)->toContain($result);
    }
});

it('falls back to the full item list once the unwon pool is exhausted', function () {
    $assign = new AssignRandomItem;

    $result = $assign([], [7, 8, 9], fn (int $min, int $max) => $min);

    expect($result)->toBe(7)
        ->and([7, 8, 9])->toContain($result);
});

it('allows a repeat once the pool is exhausted', function () {
    $assign = new AssignRandomItem;

    $first = $assign([], [42], fn (int $min, int $max) => 0);
    $second = $assign([], [42], fn (int $min, int $max) => 0);

    expect($first)->toBe(42)->and($second)->toBe(42);
});

it('passes the correct index bounds to the randomizer for the active pool', function () {
    $assign = new AssignRandomItem;
    $seenBounds = null;

    $assign([10, 20, 30], [10, 20, 30, 40], function (int $min, int $max) use (&$seenBounds) {
        $seenBounds = [$min, $max];

        return $min;
    });

    // Bounds reflect the unwon pool (3 items), not the full item list (4 items).
    expect($seenBounds)->toBe([0, 2]);
});

it('rejects a collection theme with no items at all', function () {
    $assign = new AssignRandomItem;

    expect(fn () => $assign([], []))->toThrow(InvalidArgumentException::class);
});

it('defaults to PHP\'s random_int when no randomizer is supplied', function () {
    $assign = new AssignRandomItem;

    $result = $assign([5], [5]);

    expect($result)->toBe(5);
});
