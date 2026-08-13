## Why

The existing pop-up giveaway is built for a specific shape: a short countdown where every entrant instantly wins a random item from a whole themed collection. Staff also run a different, common kind of giveaway - a single (or small number of) specific pre-set prize, open for an extended period (e.g. a week), sometimes restricted to server boosters or a particular role, that ends with staff (or the system, on schedule) drawing one or more random winners from everyone who entered. Trying to force that into the pop-up giveaway's model (random item per entrant, short timer) doesn't fit. Burrow needs a second, distinct giveaway type for this.

## What Changes

- Add **standard giveaways**: a title, description, Discord channel, one or more specific pre-set prize items (selected by searching across the guild's existing collection themes' items - not a whole theme), an eligibility restriction (booster-only and/or one or more required Discord roles, combinable, or open to everyone), a configurable winner count (default 1), a posting mode (new thread per run, or new plain message per run - reusing the Events posting-mode concept), and either no recurrence (one-off) or a full custom recurrence rule (the same RFC 5545 RRULE engine built for Events).
- Add **standard giveaway occurrences**: the generated, schedulable instances of a standard giveaway - one for a one-off giveaway, one per recurrence for a recurring one - each snapshotted from its parent giveaway at generation time (same pattern as `event-occurrences`), each with its own entrant list, its own end time, and its own posted Discord message/thread.
- Add **standard giveaway entries**: a member entering an open occurrence. Eligibility (booster status, required roles) is enforced at entry time using role/boost data the bot supplies from the Discord interaction - Laravel still never talks to Discord directly. One entry per member per occurrence.
- Add automatic closing and drawing: when an occurrence's end time passes, the system closes entries and randomly draws the configured number of winners from eligible entrants, assigning prize items fairly (reusing the pop-up giveaway's no-repeat-until-exhausted assignment rule when more than one prize item is configured) - all enforced authoritatively server-side, mirroring the pop-up giveaway's expiry guarantee.
- Extend the **discord-bot-gateway** contract: posting a standard giveaway occurrence (thread or message, with a single "Enter" control), relaying entry interactions (including the member's current roles/boost status from the interaction payload), and announcing drawn winners back in Discord.

## Capabilities

### New Capabilities
- `standard-giveaways`: the title/description/channel/prize-items/restrictions/winner-count/posting-mode/recurrence definition of a standard giveaway series.
- `standard-giveaway-occurrences`: generating, posting, and auto-closing-with-draw individual runs of a standard giveaway.
- `standard-giveaway-entries`: a member entering an open occurrence, with eligibility enforcement and one-entry-per-member.

### Modified Capabilities
- `discord-bot-gateway`: add posting a standard giveaway occurrence, relaying entry interactions (with role/boost snapshot), and announcing winners, alongside the existing giveaway and event behavior.

## Non-goals

- No new "category" or prize-taxonomy model - prize items are existing `collection_theme_items`, searched across all of a guild's collection themes. (Working assumption from the request - see design.md for the exact interpretation and where to correct it if wrong.)
- No manual "staff hand-picks the winner" mode in v1 - closure always performs a random draw among eligible entrants; a future change could add a manual-pick alternative.
- No re-opening entries or extending the end time after an occurrence has closed.
- No changing a member's already-recorded eligibility snapshot after entry - if their roles change after entering, their original entry stands (or is later excluded at draw time - see design.md for the exact cutover point).
- No notification/reminder messages (e.g. "ends in 1 hour") in v1, consistent with Events' non-goals.
- Booster/role restrictions are evaluated from the data the bot supplies at the moment of the entry interaction - Burrow does not independently sync or store full guild role membership.

## Impact

- New MySQL schema: `standard_giveaways`, `standard_giveaway_prize_items` (pivot to `collection_theme_items`), `standard_giveaway_required_roles`, `standard_giveaway_occurrences`, `standard_giveaway_entries`, `standard_giveaway_winners`.
- Reuses without modification: `collection_themes`/`collection_theme_items` (as the prize-item source), the `simshaun/recurr` RRULE engine and occurrence-generation pattern from Events, the `discord_outbound_actions` table (two new action types), and the `AssignRandomItem` fairness algorithm.
- Extends `openapi.yaml` with new internal endpoints and extends the bot process with new posting/interaction handling, following the same patterns as the Events change.
- New guild-scoped dashboard screens: create/manage standard giveaways, view an occurrence's entrants and drawn winners.
