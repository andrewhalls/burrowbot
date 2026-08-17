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
        // $anchorStart arrives carrying config('app.timezone') (UTC) as its
        // label, even though its wall-clock digits are the admin's local
        // time in $timezone (the deliberate wall-clock-local storage
        // pattern for recurrence_start_at, read back through Eloquent's
        // datetime cast). recurr uses the DateTime's own attached timezone
        // for expansion, not the separately-passed $timezone string, so
        // handing it the UTC-labelled anchor as-is would expand the rule
        // as if 18:00 local were 18:00 UTC. Re-tag the same wall-clock
        // digits into $timezone first so recurr - and every Carbon this
        // method returns - correctly represents the admin's intended local
        // instant, convertible to a true UTC instant by the caller.
        $anchorInTimezone = Carbon::parse($anchorStart->format('Y-m-d H:i:s'), $timezone);

        $rule = Rule::createFromString($rruleString, $anchorInTimezone->toDateTime(), null, $timezone);

        $transformer = new ArrayTransformer;
        $constraint = new BetweenConstraint($windowStart->toDateTime(), $windowEnd->toDateTime(), true);

        $recurrences = $transformer->transform($rule, $constraint);

        return collect($recurrences)
            ->map(fn ($recurrence) => Carbon::instance($recurrence->getStart()))
            ->values()
            ->all();
    }
}
