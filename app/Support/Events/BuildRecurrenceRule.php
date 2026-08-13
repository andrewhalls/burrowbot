<?php

declare(strict_types=1);

namespace App\Support\Events;

use Carbon\CarbonInterface;
use InvalidArgumentException;
use Recurr\Frequency;
use Recurr\Rule;

/**
 * Serializes the dashboard's structured recurrence picker (frequency,
 * interval, days of week, end condition) into an RFC 5545 RRULE string,
 * per design.md Decision 1. The raw RRULE grammar is an internal storage
 * format - admins never type it directly.
 */
class BuildRecurrenceRule
{
    private const FREQUENCY_MAP = [
        'daily' => Frequency::DAILY,
        'weekly' => Frequency::WEEKLY,
        'monthly' => Frequency::MONTHLY,
    ];

    /**
     * @param  'none'|'daily'|'weekly'|'monthly'  $type
     * @param  list<string>  $daysOfWeek  two-letter RRULE day codes (MO, TU, WE, ...), used when $type is 'weekly'
     * @param  'never'|'on_date'|'after_count'  $endType
     */
    public function __invoke(
        string $type,
        int $interval,
        array $daysOfWeek,
        string $endType,
        ?CarbonInterface $endDate,
        ?int $endCount,
        CarbonInterface $startAt,
    ): ?string {
        if ($type === 'none') {
            return null;
        }

        if (! array_key_exists($type, self::FREQUENCY_MAP)) {
            throw new InvalidArgumentException("Unknown recurrence type: {$type}");
        }

        if ($interval < 1) {
            throw new InvalidArgumentException('Recurrence interval must be at least 1.');
        }

        $rule = (new Rule)
            ->setFreq(self::FREQUENCY_MAP[$type])
            ->setInterval($interval)
            ->setStartDate($startAt->toDateTime(), false);

        if ($type === 'weekly') {
            if ($daysOfWeek === []) {
                throw new InvalidArgumentException('Select at least one day of the week for a weekly recurrence.');
            }

            $rule->setByDay($daysOfWeek);
        }

        match ($endType) {
            'never' => null,
            'on_date' => $rule->setUntil(($endDate ?? throw new InvalidArgumentException('An end date is required.'))->toDateTime()),
            'after_count' => $rule->setCount($endCount ?? throw new InvalidArgumentException('An occurrence count is required.')),
            default => throw new InvalidArgumentException("Unknown end type: {$endType}"),
        };

        return $rule->getString();
    }
}
