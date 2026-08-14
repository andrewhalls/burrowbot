## Why

Every timestamp the app stores and processes is UTC, but admins entering a scheduled start time (Popup Giveaway) or a start date/time and recurrence rule (Standard Giveaway, Events) today have their input interpreted in the *server's* configured timezone - and any already-scheduled time shown back to them is displayed in that same server timezone. For any admin not physically in that timezone, this is confusing and error-prone: there's no way to tell what time they're actually scheduling something for without doing the UTC-offset math themselves.

## What Changes

- Every date/time input across the dashboard (Popup Giveaway's optional scheduled start; Standard Giveaway's and Events' start date/time and recurrence end date) is interpreted in the admin's browser-local timezone, auto-detected via `Intl.DateTimeFormat().resolvedOptions().timeZone`, and converted to UTC before validation/save - with no way to schedule in a timezone other than the browser's own.
- **BREAKING**: Standard Giveaway's and Events' manual `timezone` text field (previously defaulting to the server's configured timezone, freely editable) is removed entirely from both the create forms and their underlying Livewire components' public API.
- Every already-persisted UTC timestamp shown anywhere in the dashboard (currently: Popup Giveaway's "Scheduled for X" list label) is displayed converted to the browser's local timezone, not the raw stored UTC value.
- A reusable convention for both directions (input and display) is established so any future date/time field or display site follows the same pattern automatically rather than reinventing it.

## Capabilities

### New Capabilities
- `browser-local-time`: the general contract for interpreting admin-entered date/time input as the browser's local timezone (converting to UTC before it reaches any existing capability's validation/business logic) and for displaying any persisted UTC timestamp back to an admin in that same local timezone. Existing capabilities (`giveaway-lifecycle`, `standard-giveaways`, `events`) already describe scheduling/recurrence behavior in terms of "a start time" without specifying which timezone that input is interpreted in - this capability defines that cross-cutting layer without changing what those capabilities themselves promise.

### Modified Capabilities
None - no existing capability's documented requirement/scenario text mentions a timezone field or a specific display timezone today (it was always an implementation detail, never part of the spec-level contract), so removing the manual `timezone` input and changing the interpreted/displayed timezone doesn't change any existing requirement's wording.

## Impact

- **Affected code**: `CreateGiveaway`, `CreateStandardGiveaway`, `CreateEvent` Livewire components and their Blade views (date/time input handling; `CreateStandardGiveaway`/`CreateEvent` lose their `timezone` property and its form field entirely); `giveaway-index.blade.php`'s "Scheduled for X" label; `resources/js/app.js` (new page-wide JS for both directions, following the same "lives in app.js, not an inline `<script>` inside a Blade component" precedent as the channel picker, since several of these forms are nested Livewire components toggled in via AJAX).
- **Multi-guild scoping**: not applicable - timezone is a property of the admin's browser, not the guild; two admins of the same guild in different timezones each see/enter times relative to their own browser, and the underlying stored UTC value is identical either way.
- **No changes** to any backend scheduling/cron/queue timing logic - `giveaways:close-expired`, `events:generate-occurrences`, `standard-giveaways:post-due-occurrences`, etc. all continue operating purely in UTC, unaffected by what an admin's browser displays.

## Non-goals

- No user-configurable "preferred timezone" setting stored anywhere - always live-detected from the browser on every page load, never persisted, never overridable.
- No change to how the bot posts times to Discord - Discord's own `<t:UNIX:R>` timestamp markup already renders in each Discord user's own local time client-side; that mechanism is untouched and already solves this problem on the Discord side.
- No fallback UI for browsers with JavaScript disabled beyond a safe, clearly-labeled UTC display and UTC-interpreted input - full timezone-awareness requires JavaScript, same as the existing channel picker.
- No retroactive re-interpretation of already-stored timestamps - only how they're *displayed* changes; the stored UTC values themselves are untouched.
