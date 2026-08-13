<?php

declare(strict_types=1);

namespace App\Support\Events;

use Illuminate\Support\Carbon;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\Constraint\BetweenConstraint;

/**
 * Expands an RRULE string into concrete occurrence start times within a
 * window, via simshaun/recurr (design.md Decision 1). Pure/side-effect
 * free - the caller decides what to do with the resulting dates.
 */
class ExpandRecurrenceRule
{
    /**
     * @return list<Carbon>
     */
    public function __invoke(
        string $rruleString,
        Carbon $anchorStart,
        string $timezone,
        Carbon $windowStart,
        Carbon $windowEnd,
    ): array {
        $rule = Rule::createFromString($rruleString, $anchorStart->clone()->toDateTime(), null, $timezone);

        $transformer = new ArrayTransformer;
        $constraint = new BetweenConstraint($windowStart->toDateTime(), $windowEnd->toDateTime(), true);

        $recurrences = $transformer->transform($rule, $constraint);

        return collect($recurrences)
            ->map(fn ($recurrence) => Carbon::instance($recurrence->getStart()))
            ->values()
            ->all();
    }
}
