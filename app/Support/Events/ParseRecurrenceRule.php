<?php

declare(strict_types=1);

namespace App\Support\Events;

use Carbon\CarbonImmutable;
use Recurr\Frequency;
use Recurr\Rule;

/**
 * The inverse of BuildRecurrenceRule: parses a stored RRULE string back
 * into the dashboard's structured recurrence picker fields, so an Edit
 * form can pre-fill from an existing series (design.md Decision 1,
 * add-giveaway-and-event-editing). Only understands the shapes
 * BuildRecurrenceRule itself produces (freq/interval/byday/until/count) -
 * an RRULE from any other source may not round-trip cleanly.
 */
class ParseRecurrenceRule
{
    private const FREQUENCY_MAP = [
        Frequency::DAILY => 'daily',
        Frequency::WEEKLY => 'weekly',
        Frequency::MONTHLY => 'monthly',
    ];

    /**
     * @return array{
     *     type: 'none'|'daily'|'weekly'|'monthly',
     *     interval: int,
     *     daysOfWeek: list<string>,
     *     endType: 'never'|'on_date'|'after_count',
     *     endDate: ?CarbonImmutable,
     *     endCount: ?int,
     * }
     */
    public function __invoke(?string $rrule): array
    {
        if ($rrule === null) {
            return [
                'type' => 'none',
                'interval' => 1,
                'daysOfWeek' => [],
                'endType' => 'never',
                'endDate' => null,
                'endCount' => null,
            ];
        }

        $rule = new Rule($rrule);

        $until = $rule->getUntil();
        $count = $rule->getCount();

        return [
            'type' => self::FREQUENCY_MAP[$rule->getFreq()] ?? 'none',
            'interval' => $rule->getInterval(),
            'daysOfWeek' => $rule->getByDay() ?? [],
            'endType' => $until !== null ? 'on_date' : ($count !== null ? 'after_count' : 'never'),
            'endDate' => $until !== null ? CarbonImmutable::instance($until) : null,
            'endCount' => $count,
        ];
    }
}
