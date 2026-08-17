## Why

A weekly (or any recurring) standard giveaway currently has exactly one way to change what it gives away: edit the series, which only affects occurrences generated *after* the edit. There's no way to set up, say, "next Sunday's item is a Golden Ticket, the one after is a Cartridge" in advance - editing the series changes the template going forward, not a specific upcoming week. The data already supports this (each generated occurrence snapshots its own `description`/`prize_item_ids` independently of the series - that's the whole mechanism "editing the series only affects future occurrences" already relies on); what's missing is a screen to edit one specific not-yet-posted occurrence in isolation.

## What Changes

- A guild admin can browse a standard giveaway series' upcoming (`scheduled`, not yet posted) occurrences and edit one directly: its description and prize items, independent of the series template and every other occurrence.
- Editing an occurrence is only available while it's `scheduled` - once posted, its description/items are what went out to Discord and stay fixed (matches the existing "editing a series never touches already-generated occurrences" rule, extended to explicit single-occurrence edits).
- Scoped to description and prize items only, per the request - not the full field set an edit-the-series form covers (channel, posting mode, winner count, eligibility, recurrence). Those are series-wide configuration; varying them week-to-week isn't what was asked for and would blur the line between "this week is different" and "the series changed."

## Capabilities

### Modified Capabilities

- `standard-giveaway-occurrences`: adds the ability to edit a single not-yet-posted occurrence's description and prize items, independent of the series.

## Impact

- Multi-guild scoping: unaffected - editing an occurrence is reached through its already-guild-scoped, already-authorized series.
- `App\Actions\StandardGiveaways\UpdateStandardGiveawayOccurrenceAction` (new): rejects unless the occurrence is still `scheduled`, otherwise overwrites `description`/`prize_item_ids` directly (both are plain columns on the occurrence, not relations - no re-sync logic needed, unlike the series-level prize item editing).
- `App\Livewire\StandardGiveaways\EditStandardGiveawayOccurrence` (new): mirrors `EditStandardGiveaway`'s prize-item search/chip UI, scoped to one occurrence.
- `App\Livewire\StandardGiveaways\StandardGiveawayIndex`: gains an "upcoming occurrences" list (scheduled occurrences for the selected series) in the detail panel, each with an Edit action.

## Non-goals

- No editing of title, image, channel, posting mode, winner count, eligibility (booster/roles), or duration per-occurrence - series-wide fields stay series-wide, changed only via editing the series (affecting future generation).
- No editing a `posted` or `closed` occurrence - what already went to Discord stays fixed, consistent with every other "no editing after it's live" rule in this app (draft-only giveaway edits, unposted-only deletes).
- No bulk/multi-occurrence editing - one occurrence at a time, matching every other edit form in this app.
