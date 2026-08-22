<?php

declare(strict_types=1);

use App\Support\Broadcasts\RenderBroadcastMessage;
use Illuminate\Support\Carbon;

it('substitutes all supported placeholders', function () {
    $render = new RenderBroadcastMessage;

    $result = $render(
        'Hi {{guild_name}}! Head to {{channel}} on {{date}} at {{time}}. Next one: {{next_occurrence_date}}.',
        'Demo Server',
        '123456',
        Carbon::parse('2026-06-03 20:00:00', 'UTC'),
        'UTC',
        Carbon::parse('2026-06-10 20:00:00', 'UTC'),
    );

    expect($result)->toBe('Hi Demo Server! Head to <#123456> on Jun 3, 2026 at 8:00pm. Next one: Jun 10, 2026.');
});

it('leaves a template with no placeholders unchanged', function () {
    $render = new RenderBroadcastMessage;

    $result = $render(
        'Plain reminder text.',
        'Demo Server',
        '123456',
        Carbon::parse('2026-06-03 20:00:00', 'UTC'),
        'UTC',
        null,
    );

    expect($result)->toBe('Plain reminder text.');
});

it('resolves next_occurrence_date to an empty string when there is none', function () {
    $render = new RenderBroadcastMessage;

    $result = $render(
        'Next: {{next_occurrence_date}}',
        'Demo Server',
        '123456',
        Carbon::parse('2026-06-03 20:00:00', 'UTC'),
        'UTC',
        null,
    );

    expect($result)->toBe('Next: ');
});

it('leaves an unrecognized placeholder as literal text', function () {
    $render = new RenderBroadcastMessage;

    $result = $render(
        'Hello {{typo_field}}!',
        'Demo Server',
        '123456',
        Carbon::parse('2026-06-03 20:00:00', 'UTC'),
        'UTC',
        null,
    );

    expect($result)->toBe('Hello {{typo_field}}!');
});

it('resolves date and time in the given timezone, not UTC', function () {
    $render = new RenderBroadcastMessage;

    // 2026-06-03 20:00 UTC is 2026-06-03 16:00 in America/New_York (EDT, UTC-4).
    $result = $render(
        '{{date}} {{time}}',
        'Demo Server',
        '123456',
        Carbon::parse('2026-06-03 20:00:00', 'UTC'),
        'America/New_York',
        null,
    );

    expect($result)->toBe('Jun 3, 2026 4:00pm');
});
