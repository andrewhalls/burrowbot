<?php

declare(strict_types=1);

namespace App\Support\Giveaways;

use Closure;
use InvalidArgumentException;

/**
 * Pure function implementing design.md §3's assignment rule: draw without
 * replacement from the items nobody has won yet in this giveaway; once
 * every item has been won at least once, draw with replacement from the
 * full item list instead of rejecting the entrant.
 *
 * The random source is injectable so tests can make selection deterministic
 * without touching PHP's global RNG state.
 */
final class AssignRandomItem
{
    /**
     * @param  list<int>  $unwonItemIds  item ids nobody in this giveaway has won yet
     * @param  list<int>  $allItemIds  every item id belonging to the giveaway's collection theme
     * @param  Closure(int,int):int|null  $randomInt  defaults to random_int(); receives [min, max], returns an index
     */
    public function __invoke(array $unwonItemIds, array $allItemIds, ?Closure $randomInt = null): int
    {
        if ($allItemIds === []) {
            throw new InvalidArgumentException('A collection theme with no items cannot back a giveaway entry.');
        }

        $pool = $unwonItemIds !== [] ? $unwonItemIds : $allItemIds;
        $pool = array_values($pool);

        $randomInt ??= random_int(...);
        $index = $randomInt(0, count($pool) - 1);

        return $pool[$index];
    }
}
