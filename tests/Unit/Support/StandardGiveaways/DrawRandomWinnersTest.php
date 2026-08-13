<?php

declare(strict_types=1);

use App\Support\StandardGiveaways\DrawRandomWinners;

it('draws exactly the requested count when enough entries exist', function () {
    $draw = new DrawRandomWinners;

    $result = $draw([1, 2, 3, 4, 5], 3, fn (int $min, int $max) => $min);

    expect($result)->toHaveCount(3);
});

it('draws distinct entries, never repeating one', function () {
    $draw = new DrawRandomWinners;

    // Forced randomizer always picks index 0 - if not removed from the
    // pool between draws, this would return the same id three times.
    $result = $draw([10, 20, 30], 3, fn (int $min, int $max) => $min);

    expect($result)->toEqualCanonicalizing([10, 20, 30]);
});

it('draws every available entry when there are fewer entries than the winner count', function () {
    $draw = new DrawRandomWinners;

    $result = $draw([1, 2], 5, fn (int $min, int $max) => $min);

    expect($result)->toHaveCount(2)
        ->and($result)->toEqualCanonicalizing([1, 2]);
});

it('returns an empty list when the pool is empty', function () {
    $draw = new DrawRandomWinners;

    $result = $draw([], 3);

    expect($result)->toBe([]);
});

it('rejects a winner count below 1', function () {
    $draw = new DrawRandomWinners;

    expect(fn () => $draw([1, 2, 3], 0))->toThrow(InvalidArgumentException::class);
});

it('respects the injected randomizer for deterministic selection', function () {
    $draw = new DrawRandomWinners;

    // Always pick the last remaining element.
    $result = $draw([1, 2, 3], 2, fn (int $min, int $max) => $max);

    expect($result)->toBe([3, 2]);
});
