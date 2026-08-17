## Why

There's currently no way to remove a popup giveaway, standard giveaway, or event from a guild's dashboard - a draft created by mistake, or a recurring series set up wrong, stays there forever (the only lifecycle actions are start/edit or active/paused/cancelled).

## What Changes

- A guild admin can delete a popup giveaway, standard giveaway, or event, but only while nothing about it has been posted to Discord yet: a popup giveaway while still `draft`; a standard giveaway or event series while every occurrence it has (if any) is still `scheduled` (none `posted`/`closed`). Deleting removes the row and, via existing cascading foreign keys, everything scoped under it (occurrences, entries/signups, prize items/roles, outbound actions) - all of it pre-Discord data already, since deletion is blocked once anything is live.
- Anything already posted keeps using the existing cancel/pause lifecycle instead - deletion is deliberately not offered there, so no Discord message is ever silently orphaned by a delete.
- Confirmation is required before deleting (destructive, unrecoverable).

## Capabilities

### Modified Capabilities

- `giveaway-lifecycle`: adds deletion of a still-`draft` giveaway.
- `standard-giveaways`: adds deletion of a series with no posted/closed occurrences.
- `events`: adds deletion of a series with no posted occurrences.

## Impact

- Multi-guild scoping: unaffected - delete actions operate on an already-guild-scoped, already-authorized row.
- `App\Actions\Giveaways\DeleteGiveawayDraftAction`, `App\Actions\StandardGiveaways\DeleteStandardGiveawayAction`, `App\Actions\Events\DeleteEventAction` (new) - each a guard-then-`delete()`, relying on the already-`cascadeOnDelete()` foreign keys throughout (verified: every child table - entries, occurrences, signups, prize items/required roles, outbound actions, winners, attendances - already cascades).
- `App\Livewire\Giveaways\GiveawayDashboard`, `App\Livewire\StandardGiveaways\StandardGiveawayIndex`, `App\Livewire\Events\EventIndex` (new `delete()` methods) and their Blade views (Delete button + confirmation).

## Non-goals

- No delete for anything already posted to Discord - that stays on the existing cancel/pause lifecycle, per the user's explicit choice of the narrower delete scope (no new bot-side message-deletion capability).
- No soft-delete/trash/undo - deletion is immediate and permanent, matching the plain word "delete" the user asked for.
- No bulk delete - one item at a time, matching every other lifecycle action in this app.
