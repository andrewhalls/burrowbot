## Why

Cancelled/finished events and standard giveaways currently stay in the admin's list forever - there's no way to declutter historical series short of permanently deleting them, and permanent delete is already blocked once any occurrence has posted. Admins need a way to tuck away series they're done with while keeping the history available on demand.

## What Changes

- Add a nullable `archived_at` timestamp to both `events` and `standard_giveaways`.
- Add an Archive action to both series types: sets `status` to `cancelled` (from any current status) and stamps `archived_at`, in one step - matches the existing Cancel action's generation-stopping effect, so archiving something that's still active also stops it from posting further occurrences.
- Add an Unarchive action to both: clears `archived_at` only. The series stays `cancelled`; the admin can separately hit Activate if they want it live again.
- `EventIndex` and `StandardGiveawayIndex` default their list query to exclude archived series (`whereNull('archived_at')`).
- Both index screens gain a "Show archived" toggle that, when on, adds archived series back into the visible list alongside everything else (not an exclusive "archived-only" filter).
- Archived series are otherwise unaffected everywhere else in the app - occurrence generation/posting commands, dashboard-home, and every other query already only care about `status`, not this new column.

## Capabilities

### New Capabilities

(none - this extends two existing capabilities)

### Modified Capabilities

- `events`: adds archiving/unarchiving and the default-hidden list behavior with a "Show archived" toggle.
- `standard-giveaways`: same, for standard giveaway series.

## Non-goals

- No new `status` value - archiving reuses the existing `cancelled` status plus a separate `archived_at` marker, rather than introducing a fourth status.
- No change to the existing permanent-delete rule (`isDeletable()`) or to the existing free Activate/Pause/Cancel status-transition behavior.
- No bulk-archive action and no automatic/scheduled archiving (e.g. "archive after N days cancelled") - this is a manual, per-series toggle only.
- No change to any other screen or query in the app (dashboard-home summaries, occurrence generation/posting, member-facing views) - `archived_at` is read only by the two staff-facing index list queries this change touches.

## Impact

- **Multi-guild scoping**: `archived_at` lives on `events`/`standard_giveaways`, both already guild-scoped via `guild_id`; the new list-query filter and the "Show archived" toggle apply per the currently-viewed guild exactly like the rest of each index screen already does - no cross-guild exposure risk.
- **Affected code**: one migration adding `archived_at` to both tables; `app/Models/Event.php` and `app/Models/StandardGiveaway.php` (fillable/casts, a `isArchived()` helper); four new small actions (`ArchiveEventAction`, `UnarchiveEventAction`, `ArchiveStandardGiveawayAction`, `UnarchiveStandardGiveawayAction`); `app/Livewire/Events/EventIndex.php` and `app/Livewire/StandardGiveaways/StandardGiveawayIndex.php` (new `showArchived` property, filtered query, new action-dispatch methods); `resources/views/livewire/events/event-index.blade.php` and `resources/views/livewire/standard-giveaways/standard-giveaway-index.blade.php` (per-tile Archive/Unarchive button alongside each screen's existing Activate/Pause/Cancel buttons, plus a "Show archived" toggle control in both list views).
- **No impact** on occurrence generation/posting commands, the bot, dashboard-home, or the popup `Giveaway` model (out of scope, not one of the two capabilities being changed).
