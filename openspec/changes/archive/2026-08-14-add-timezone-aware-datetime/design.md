## Context

See proposal.md - Why. Relevant existing pieces this design builds on:

- `CreateGiveaway`, `CreateStandardGiveaway`, `CreateEvent` each combine a date string and a time string into a Carbon instance via `Carbon::parse("{$date} {$time}", $timezone)`, where `$timezone` is today either `config('app.timezone')` (Popup Giveaway) or a manually-typed Livewire property defaulting to it (Standard Giveaway, Events).
- The channel picker (`resources/views/components/channel-picker.blade.php`, JS in `resources/js/app.js`) already established this codebase's answer to "where does page-wide JS that needs to work regardless of how markup enters the DOM live": `resources/js/app.js`, using `document`-level event delegation, never an inline `<script>` inside a Blade component. Several of the forms this change touches (`CreateGiveaway`, `CreateStandardGiveaway`, `CreateEvent`) are nested Livewire components toggled into existence via an AJAX `wire:click="$toggle(...)"`, so the same constraint applies here: a `<script>` tag injected via Livewire's DOM morphing never executes.
- Discord's own message embeds already use `<t:UNIX:R>` timestamp markup, which Discord's client renders in each viewer's own local time automatically - this change never touches that path (proposal.md - Non-goals).

## Goals / Non-Goals

**Goals:**
- Every scheduling input is interpreted in the browser's local timezone; every display of a persisted timestamp shows that same local timezone.
- One reusable mechanism for each direction (input, display), not a bespoke solution per form.
- No flash-of-wrong-time complexity that isn't worth its cost - a brief, honest UTC-labeled display before JS runs is an acceptable trade-off for avoiding a full round-trip/cookie/reload dance.

**Non-Goals:**
- No persisted timezone preference (proposal.md).
- No server-side timezone detection of any kind (no cookie, no header sniffing) - browsers don't send their IANA timezone in any HTTP header, so server-side detection isn't actually possible without a prior round trip; this design doesn't attempt one.

## Decisions

### Decision 1a (discovered during implementation): recurrence-capable forms keep wall-clock-local Carbon instances, they don't convert to UTC
`CreateStandardGiveaway` and `CreateEvent` both already store a separate `recurrence_timezone` column alongside `recurrence_start_at`, consumed by `ExpandRecurrenceRule`, which passes that timezone straight to `simshaun/recurr` alongside the SAME wall-clock numbers `recurrence_start_at` carries - `recurr` reads the wall-clock numbers off the given `DateTime` and reinterprets them in the given timezone itself, regardless of what timezone label the `DateTime` was originally constructed with. This means `recurrence_start_at` (and `recurrenceEndDate`, fed into the same RRULE-building step) must keep the admin's literal local wall-clock numbers (e.g. "20:00"), NOT the UTC-converted equivalent - converting them would silently shift every future generated occurrence by the timezone offset, since `ExpandRecurrenceRule` would then reinterpret the (already-shifted) UTC wall-clock numbers as if they were local time again, double-applying the offset.

This is unrelated to `CreateGiveaway`'s Popup Giveaway `scheduledStartAt`, which has no recurrence concept at all and is compared directly against `now()` (a true UTC instant) - that one correctly *does* need `->utc()`.

This pattern (wall-clock-local Carbon + a separately-stored timezone label, rather than a single true-UTC instant) already existed before this change - previously "local" and UTC always coincided since `config('app.timezone')` is `'UTC'` and that was the only value ever used, masking the distinction. This change is the first time `resolvedTimezone()` can genuinely differ from UTC, which is what surfaced the need to be explicit about which of the two conventions each field follows.

### Decision 1: Input direction uses a hidden field populated by JS, mirroring the channel picker's `wire:model`-carrying-hidden-input pattern
Each of `CreateGiveaway`, `CreateStandardGiveaway`, `CreateEvent` gains a `public string $browserTimezone = 'UTC';` property (via a shared trait, Decision 3) and a hidden `<input type="hidden" wire:model="browserTimezone" data-browser-timezone-input>` rendered once per form. A single document-level listener in `resources/js/app.js` (registered once, alongside the existing channel-picker listeners) finds every `[data-browser-timezone-input]` element present at `DOMContentLoaded` and on Livewire's `livewire:navigated`/component-morph events, sets its value to `Intl.DateTimeFormat().resolvedOptions().timeZone`, and dispatches an `input` event so Livewire's `wire:model` picks it up - exactly the same trick already used to wire the channel picker's selected channel ID back to its parent component. By the time an admin actually submits the form, `browserTimezone` already holds the real value; no page reload or cookie round-trip is needed for this direction, since the value only needs to be correct at submit time, not at initial server render time.

`UTC` is the property's default (used if JS never runs) - this is what makes "browser timezone cannot be detected" degrade to UTC rather than a validation error (spec scenario).

**Alternative considered**: a cookie set by JS and read server-side on every request. Rejected for the input direction specifically - it doesn't need to be known before the page first renders, only before the user submits, so a cookie's only theoretical advantage (knowing the timezone at *initial* server render) is irrelevant here and it adds a real cost: a cookie is stale the instant a user's system timezone changes (e.g. daylight saving, or physically traveling) until they reload, whereas the hidden-input approach re-detects fresh on every single page load.

### Decision 2: Display direction is pure client-side re-formatting, not server-side conversion
Any Blade location that shows a persisted UTC timestamp renders it via a new `<x-local-time :at="$utcTimestamp" />` component, outputting `<time data-utc-datetime="{{ $utcTimestamp->toIso8601String() }}">{{ $utcTimestamp->format('M j, Y g:ia') }} UTC</time>` - a real, honest UTC-labeled value as the initial/fallback content. The same `resources/js/app.js` listener that handles the browser-timezone hidden inputs also scans for `[data-utc-datetime]` elements on `DOMContentLoaded` and after any Livewire morph, and replaces each one's text with `new Date(el.dataset.utcDatetime).toLocaleString(undefined, {...})` - `toLocaleString` without an explicit `timeZone` option already uses the browser's own local timezone, so no separate timezone-detection call is needed for this direction at all.

**Alternative considered**: a `tz` cookie set by JS and read by Blade to pre-convert timestamps before the server ever sends HTML, avoiding any client-side rewrite/flash. Rejected - on a user's very first-ever page view there is no cookie yet, so the server would still render in the wrong (server-default) timezone regardless; "fix it up client-side after load" is needed either way as a fallback, so building the cookie machinery on top would only add complexity (cookie parsing, staleness, an extra round trip once the timezone is wrong) without actually eliminating the client-side rewrite step it was meant to avoid. A brief, clearly UTC-labeled flash before JS relabels it in local time is an acceptable, honest trade-off - it never shows an admin an *incorrect* time, only a correctly-labeled UTC one for a moment.

### Decision 3: `browserTimezone` property and UTC-conversion helper live in a shared Livewire trait
`App\Livewire\Concerns\ResolvesBrowserTimezone` provides the `public string $browserTimezone = 'UTC';` property plus a `resolvedTimezone(): string` method (validates `$this->browserTimezone` is a real IANA identifier via the same check `timezone()` Laravel validation rule uses, falling back to `'UTC'` if not - defensive against a tampered/malformed hidden-field value, not just an absent one). `CreateGiveaway`, `CreateStandardGiveaway`, `CreateEvent` all `use` it instead of each declaring their own `timezone`-adjacent property and re-implementing the same fallback logic three times.

**Alternative considered**: leave `browserTimezone` as three separate, independently-declared properties. Rejected - the validation/fallback logic is identical in all three call sites, and a trait keeps that single source of truth (a bug fix in one place fixes all three forms), consistent with this codebase's existing preference for small focused Actions/traits over duplicated Livewire-component logic.

## Risks / Trade-offs

- **[Risk]** The brief UTC-labeled flash before JS relabels a displayed time in local time (Decision 2) could look like a bug to an admin who never sees it settle (e.g. a very slow network). → **Mitigation**: the flash shows an honestly-labeled UTC value, never a silently-wrong one, and JS execution/relabeling happens well before most admins would notice on any normal connection - accepted as the simplest correct behavior rather than engineering around an edge case that's never actively misleading.
- **[Risk]** `browserTimezone` defaulting to `'UTC'` when JS is disabled means an admin without JS silently gets UTC-interpreted input with no indication their local time wasn't used. → **Mitigation**: accepted per proposal.md's Non-goals - full timezone-awareness already requires JavaScript in this codebase (the channel picker has the same requirement), and a clear fallback to a well-known, unambiguous timezone (UTC) beats either blocking the form entirely or guessing wrong.

## Migration Plan

No database migration - this change touches no persisted schema (timestamps are already stored as UTC `datetime` columns; nothing about their storage format changes). Purely additive/removal at the Livewire-property and Blade-view level: removing `CreateStandardGiveaway`/`CreateEvent`'s `timezone` property and field is the only breaking surface, and it's dashboard-form-only - no API consumer or the bot process ever reads that property today, so nothing outside these three Livewire components is affected.
