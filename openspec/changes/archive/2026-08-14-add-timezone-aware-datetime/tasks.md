## 1. Shared trait

- [x] 1.1 `App\Livewire\Concerns\ResolvesBrowserTimezone`: `public string $browserTimezone = 'UTC';` + `resolvedTimezone(): string` (validates via the same check Laravel's `timezone` validation rule uses, falling back to `'UTC'` on anything invalid/empty) (design.md Decision 3)
- [x] 1.2 Pest test: `resolvedTimezone()` returns a valid IANA value unchanged; returns `'UTC'` for an invalid/empty value

## 2. Input direction (JS + hidden field)

- [x] 2.1 `resources/js/app.js`: on `DOMContentLoaded` and Livewire's morph/navigated events, find every `[data-browser-timezone-input]` element, set its value to `Intl.DateTimeFormat().resolvedOptions().timeZone`, dispatch an `input` event (design.md Decision 1 - same pattern as the channel picker's hidden input)
- [x] 2.2 A small reusable Blade partial/component rendering `<input type="hidden" wire:model="browserTimezone" data-browser-timezone-input>` (avoid repeating the raw markup 3x)

## 3. Wire into the 3 create forms

- [x] 3.1 `CreateGiveaway`: `use ResolvesBrowserTimezone`; `scheduledStartDate`/`scheduledStartTime` parsed via `Carbon::parse(..., $this->resolvedTimezone())->utc()` instead of `config('app.timezone')`; render the hidden timezone input once in its Blade view
- [x] 3.2 `CreateStandardGiveaway`: `use ResolvesBrowserTimezone`; remove the `timezone` property and its form field entirely; `startDate`/`startTime`/`recurrenceEndDate` parsed via `$this->resolvedTimezone()` (deliberately NOT `->utc()` - see code comment: ExpandRecurrenceRule needs wall-clock-local numbers paired with the stored `recurrence_timezone`); render the hidden timezone input
- [x] 3.3 `CreateEvent`: same treatment as 3.2 - remove `timezone`, use `resolvedTimezone()`, render the hidden timezone input
- [x] 3.4 Pest/Livewire tests per form: setting `browserTimezone` to a non-UTC value and submitting a date/time results in the correct UTC-converted persisted timestamp (Popup Giveaway) or the correct unconverted wall-clock + timezone pairing (Standard Giveaway/Event); an invalid `browserTimezone` falls back to UTC interpretation rather than erroring

## 4. Display direction

- [x] 4.1 `resources/views/components/local-time.blade.php`: `<x-local-time :at="$utcTimestamp" />` rendering `<time data-utc-datetime="{{ $at->toIso8601String() }}">{{ $at->format('M j, Y g:ia') }} UTC</time>` (design.md Decision 2)
- [x] 4.2 `resources/js/app.js`: on the same events as 2.1, find every `[data-utc-datetime]` element and replace its text content with `new Date(el.dataset.utcDatetime).toLocaleString(...)` (browser-local by default, no explicit timeZone option needed)
- [x] 4.3 Replace `giveaway-index.blade.php`'s raw `$giveaway->scheduled_start_at->format(...)` with `<x-local-time :at="$giveaway->scheduled_start_at" />`
- [x] 4.4 Pest test: the giveaway list view renders the `data-utc-datetime` attribute with the correct ISO8601 UTC value for a scheduled giveaway

## 5. Verification

- [x] 5.1 Full Pest suite passes
- [x] 5.2 `openspec validate add-timezone-aware-datetime --strict` passes
