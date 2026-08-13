<?php

declare(strict_types=1);

namespace App\Support\StandardGiveaways;

use Closure;
use InvalidArgumentException;

/**
 * Pure function: draws min($count, count($pool)) distinct ids at random,
 * without replacement, from a candidate pool. Used to pick winning entries
 * from an occurrence's eligible entrants (design.md Decision 3).
 *
 * The random source is injectable so tests can make selection deterministic
 * without touching PHP's global RNG state - mirrors AssignRandomItem.
 */
final class DrawRandomWinners
{
    /**
     * @param  list<int>  $pool
     * @param  Closure(int,int):int|null  $randomInt  defaults to random_int(); receives [min, max], returns an index
     * @return list<int>
     */
    public function __invoke(array $pool, int $count, ?Closure $randomInt = null): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Winner count must be at least 1.');
        }

        $remaining = array_values($pool);
        $randomInt ??= random_int(...);
        $drawn = [];

        $winnersToTake = min($count, count($remaining));

        for ($i = 0; $i < $winnersToTake; $i++) {
            $index = $randomInt(0, count($remaining) - 1);
            $drawn[] = $remaining[$index];
            array_splice($remaining, $index, 1);
        }

        return $drawn;
    }
}
