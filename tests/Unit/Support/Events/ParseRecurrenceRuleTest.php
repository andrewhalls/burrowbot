<?php

declare(strict_types=1);

use App\Support\Events\BuildRecurrenceRule;
use App\Support\Events\ParseRecurrenceRule;
use Illuminate\Support\Carbon;

it('returns the none shape for a null rrule', function () {
    $parse = new ParseRecurrenceRule;

    $result = $parse(null);

    expect($result['type'])->toBe('none')
        ->and($result['daysOfWeek'])->toBe([])
        ->and($result['endType'])->toBe('never');
});

it('round-trips a weekly rule with no end', function () {
    $build = new BuildRecurrenceRule;
    $rrule = $build('weekly', 1, ['WE'], 'never', null, null, Carbon::parse('2026-01-07 20:00'));

    $result = (new ParseRecurrenceRule)($rrule);

    expect($result['type'])->toBe('weekly')
        ->and($result['interval'])->toBe(1)
        ->and($result['daysOfWeek'])->toBe(['WE'])
        ->and($result['endType'])->toBe('never')
        ->and($result['endDate'])->toBeNull()
        ->and($result['endCount'])->toBeNull();
});

it('round-trips a daily rule with an interval', function () {
    $build = new BuildRecurrenceRule;
    $rrule = $build('daily', 3, [], 'never', null, null, Carbon::parse('2026-01-01'));

    $result = (new ParseRecurrenceRule)($rrule);

    expect($result['type'])->toBe('daily')
        ->and($result['interval'])->toBe(3);
});

it('round-trips a rule with an until date', function () {
    $build = new BuildRecurrenceRule;
    $rrule = $build('weekly', 1, ['MO'], 'on_date', Carbon::parse('2026-06-01'), null, Carbon::parse('2026-01-05'));

    $result = (new ParseRecurrenceRule)($rrule);

    expect($result['endType'])->toBe('on_date')
        ->and($result['endDate'])->not->toBeNull()
        ->and($result['endDate']->toDateString())->toBe('2026-06-01');
});

it('round-trips a rule with a count', function () {
    $build = new BuildRecurrenceRule;
    $rrule = $build('weekly', 1, ['MO'], 'after_count', null, 10, Carbon::parse('2026-01-05'));

    $result = (new ParseRecurrenceRule)($rrule);

    expect($result['endType'])->toBe('after_count')
        ->and($result['endCount'])->toBe(10);
});
