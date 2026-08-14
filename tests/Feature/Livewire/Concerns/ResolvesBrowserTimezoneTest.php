<?php

declare(strict_types=1);

use App\Livewire\Concerns\ResolvesBrowserTimezone;

function withBrowserTimezone(string $value): object
{
    return new class($value)
    {
        use ResolvesBrowserTimezone;

        public function __construct(string $value)
        {
            $this->browserTimezone = $value;
        }
    };
}

it('returns a valid IANA timezone unchanged', function () {
    expect(withBrowserTimezone('America/New_York')->resolvedTimezone())->toBe('America/New_York');
});

it('falls back to UTC for an invalid timezone value', function () {
    expect(withBrowserTimezone('Not/A_Timezone')->resolvedTimezone())->toBe('UTC');
});

it('falls back to UTC for an empty value', function () {
    expect(withBrowserTimezone('')->resolvedTimezone())->toBe('UTC');
});

it('defaults to UTC when never set', function () {
    $instance = new class
    {
        use ResolvesBrowserTimezone;
    };

    expect($instance->resolvedTimezone())->toBe('UTC');
});
