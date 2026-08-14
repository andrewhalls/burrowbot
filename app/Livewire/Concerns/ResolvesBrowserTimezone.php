<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Gives a Livewire component a `browserTimezone` property, auto-populated
 * client-side from `Intl.DateTimeFormat().resolvedOptions().timeZone` (see
 * resources/js/app.js) via a hidden `wire:model`-bound input rendered by
 * `resources/views/components/browser-timezone-input.blade.php`.
 *
 * See openspec specs/browser-local-time - "Date/time input is interpreted
 * in the browser's local timezone"; design.md Decision 1 and Decision 3.
 */
trait ResolvesBrowserTimezone
{
    public string $browserTimezone = 'UTC';

    /**
     * Falls back to UTC on anything that isn't a real IANA timezone
     * identifier - covers both "JavaScript never ran" (property stays at
     * its 'UTC' default) and a tampered/malformed hidden-field value.
     */
    public function resolvedTimezone(): string
    {
        try {
            new \DateTimeZone($this->browserTimezone);

            return $this->browserTimezone;
        } catch (\Exception) {
            return 'UTC';
        }
    }
}
