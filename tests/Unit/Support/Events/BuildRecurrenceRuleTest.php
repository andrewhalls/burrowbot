<?php

declare(strict_types=1);

use App\Support\Events\BuildRecurrenceRule;
use Illuminate\Support\Carbon;

it('returns null for a one-off (none) recurrence', function () {
    $build = new BuildRecurrenceRule;

    $result = $build('none', 1, [], 'never', null, null, Carbon::parse('2026-01-01 20:00'));

    expect($result)->toBeNull();
});

it('builds a weekly rule with days of week and no end', function () {
    $build = new BuildRecurrenceRule;

    $result = $build('weekly', 1, ['WE'], 'never', null, null, Carbon::parse('2026-01-07 20:00'));

    expect($result)->toContain('FREQ=WEEKLY')
        ->and($result)->toContain('BYDAY=WE');
});

it('builds a daily rule with an interval', function () {
    $build = new BuildRecurrenceRule;

    $result = $build('daily', 3, [], 'never', null, null, Carbon::parse('2026-01-01'));

    expect($result)->toContain('FREQ=DAILY')
        ->and($result)->toContain('INTERVAL=3');
});

it('builds a rule with an until date', function () {
    $build = new BuildRecurrenceRule;

    $result = $build('weekly', 1, ['MO'], 'on_date', Carbon::parse('2026-06-01'), null, Carbon::parse('2026-01-05'));

    expect($result)->toContain('UNTIL=');
});

it('builds a rule with a count', function () {
    $build = new BuildRecurrenceRule;

    $result = $build('weekly', 1, ['MO'], 'after_count', null, 10, Carbon::parse('2026-01-05'));

    expect($result)->toContain('COUNT=10');
});

it('rejects a weekly recurrence with no days of week selected', function () {
    $build = new BuildRecurrenceRule;

    expect(fn () => $build('weekly', 1, [], 'never', null, null, Carbon::parse('2026-01-05')))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects an on_date end with no end date', function () {
    $build = new BuildRecurrenceRule;

    expect(fn () => $build('daily', 1, [], 'on_date', null, null, Carbon::parse('2026-01-05')))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects an after_count end with no count', function () {
    $build = new BuildRecurrenceRule;

    expect(fn () => $build('daily', 1, [], 'after_count', null, null, Carbon::parse('2026-01-05')))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects an interval below 1', function () {
    $build = new BuildRecurrenceRule;

    expect(fn () => $build('daily', 0, [], 'never', null, null, Carbon::parse('2026-01-05')))
        ->toThrow(InvalidArgumentException::class);
});
