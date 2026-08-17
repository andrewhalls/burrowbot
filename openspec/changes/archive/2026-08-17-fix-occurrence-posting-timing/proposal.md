## Why

Discovered while scoping "pre-fill several future weeks of a standard giveaway in advance": `PostDueEventOccurrences` and `PostDueStandardGiveawayOccurrences` post *every* `scheduled` occurrence unconditionally, with no check against its scheduled time - both explicitly documented as a "v1" simplification. Combined with occurrence generation creating several weeks' worth of future occurrences in a single run (a 30-day rolling window), a weekly recurring series posts its next ~4 occurrences in one burst the moment they're generated, not one per week as the series is configured to do. This makes "schedule this in advance" not actually work for any sub-30-day recurring series today, for both Events and Standard Giveaways.

## What Changes

- `PostDueEventOccurrences`/`PostDueStandardGiveawayOccurrences` only post occurrences whose scheduled time has actually arrived (`scheduled_start_at`/`scheduled_post_at` <= now), matching what both specs already say ("WHEN an occurrence is due to be posted") - made explicit rather than implicit.
- The occurrence-generation rolling window widens from 30 to 90 days (`GenerateEventOccurrences`/`GenerateStandardGiveawayOccurrences`), so a weekly series has ~12 weeks of occurrences to pre-fill in advance instead of ~4 - safe to widen now that posting actually waits for the right date.
- The standard giveaway per-occurrence edit form (`EditStandardGiveawayOccurrence`, from the previous change) gains an image field alongside description and prize items, so a pre-filled future week can also get its own image.

## Capabilities

### Modified Capabilities

- `event-occurrences`: "Posting an occurrence to Discord" gains an explicit due-time condition.
- `standard-giveaway-occurrences`: "Posting an occurrence to Discord" gains an explicit due-time condition; "Editing a single upcoming occurrence" gains an image field.

## Impact

- Multi-guild scoping: unaffected - a timing/window correctness fix, no change to authorization or guild scoping.
- **Behavior change for any already-live recurring series**: occurrences that exist today with a future `scheduled_start_at`/`scheduled_post_at` and `status = scheduled` will, after this fix, correctly wait instead of posting on the next cron tick. Nothing already `posted` is affected.
- `app/Console/Commands/PostDueEventOccurrences.php`, `app/Console/Commands/PostDueStandardGiveawayOccurrences.php`, `app/Console/Commands/GenerateEventOccurrences.php`, `app/Console/Commands/GenerateStandardGiveawayOccurrences.php`.
- `App\Actions\StandardGiveaways\UpdateStandardGiveawayOccurrenceAction`, `App\Livewire\StandardGiveaways\EditStandardGiveawayOccurrence` and its view.
- Existing Pest tests for both `PostDue*` commands currently create occurrences with a *future* scheduled time and assert they post - that was asserting the bug. Those get corrected to explicit past-vs-future cases.

## Non-goals

- No configurable per-series "how far in advance to post" lead time - occurrences still post as soon as their scheduled time arrives, just no longer *before* it.
- No occurrence-level image/description editing for Events in this change - the per-occurrence edit form stays Standard-Giveaway-only, per the scope already established; only the shared due-time/window fix touches Events.
- No backfill/correction for any occurrence that may have already posted early under the old behavior - out of scope, and this environment has no way to inspect production data to know if it happened.
