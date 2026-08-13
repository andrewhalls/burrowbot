<?php

declare(strict_types=1);

use App\Support\Events\ExpandRecurrenceRule;
use Illuminate\Support\Carbon;

it('expands a weekly rule to the correct dates within a window', function () {
    $expand = new ExpandRecurrenceRule;

    // Anchor: Wednesday 2026-01-07 20:00 UTC, weekly on Wednesday.
    $anchor = Carbon::parse('2026-01-07 20:00:00', 'UTC');

    $dates = $expand(
        'FREQ=WEEKLY;BYDAY=WE',
        $anchor,
        'UTC',
        Carbon::parse('2026-01-01 00:00:00', 'UTC'),
        Carbon::parse('2026-01-31 23:59:59', 'UTC'),
    );

    $formatted = collect($dates)->map(fn (Carbon $d) => $d->toDateString())->all();

    expect($formatted)->toBe(['2026-01-07', '2026-01-14', '2026-01-21', '2026-01-28']);
});

it('preserves the time of day from the anchor', function () {
    $expand = new ExpandRecurrenceRule;
    $anchor = Carbon::parse('2026-01-07 20:30:00', 'UTC');

    $dates = $expand(
        'FREQ=WEEKLY;BYDAY=WE',
        $anchor,
        'UTC',
        Carbon::parse('2026-01-01 00:00:00', 'UTC'),
        Carbon::parse('2026-01-15 00:00:00', 'UTC'),
    );

    expect($dates[0]->format('H:i'))->toBe('20:30');
});

it('respects an interval greater than 1', function () {
    $expand = new ExpandRecurrenceRule;
    $anchor = Carbon::parse('2026-01-01 12:00:00', 'UTC');

    $dates = $expand(
        'FREQ=DAILY;INTERVAL=3',
        $anchor,
        'UTC',
        Carbon::parse('2026-01-01 00:00:00', 'UTC'),
        Carbon::parse('2026-01-10 23:59:59', 'UTC'),
    );

    $formatted = collect($dates)->map(fn (Carbon $d) => $d->toDateString())->all();

    expect($formatted)->toBe(['2026-01-01', '2026-01-04', '2026-01-07', '2026-01-10']);
});

it('returns an empty list when the window is entirely before the anchor', function () {
    $expand = new ExpandRecurrenceRule;
    $anchor = Carbon::parse('2026-06-01 12:00:00', 'UTC');

    $dates = $expand(
        'FREQ=WEEKLY;BYDAY=MO',
        $anchor,
        'UTC',
        Carbon::parse('2026-01-01 00:00:00', 'UTC'),
        Carbon::parse('2026-01-31 00:00:00', 'UTC'),
    );

    expect($dates)->toBe([]);
});

it('expands a monthly rule', function () {
    $expand = new ExpandRecurrenceRule;
    $anchor = Carbon::parse('2026-01-15 18:00:00', 'UTC');

    $dates = $expand(
        'FREQ=MONTHLY;BYMONTHDAY=15',
        $anchor,
        'UTC',
        Carbon::parse('2026-01-01 00:00:00', 'UTC'),
        Carbon::parse('2026-04-01 00:00:00', 'UTC'),
    );

    $formatted = collect($dates)->map(fn (Carbon $d) => $d->toDateString())->all();

    expect($formatted)->toBe(['2026-01-15', '2026-02-15', '2026-03-15']);
});

it('stops generating once an UNTIL date is passed', function () {
    $expand = new ExpandRecurrenceRule;
    $anchor = Carbon::parse('2026-01-07 20:00:00', 'UTC');

    $dates = $expand(
        'FREQ=WEEKLY;BYDAY=WE;UNTIL=20260114T200000Z',
        $anchor,
        'UTC',
        Carbon::parse('2026-01-01 00:00:00', 'UTC'),
        Carbon::parse('2026-02-28 00:00:00', 'UTC'),
    );

    $formatted = collect($dates)->map(fn (Carbon $d) => $d->toDateString())->all();

    expect($formatted)->toBe(['2026-01-07', '2026-01-14']);
});
