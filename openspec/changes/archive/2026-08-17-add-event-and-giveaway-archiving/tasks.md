## 1. Data model

- [x] 1.1 Create a migration adding nullable `archived_at` (timestamp) to both `events` and `standard_giveaways`
- [x] 1.2 Add `archived_at` to `Event`'s `$fillable`/casts (`datetime`) and add an `isArchived(): bool` helper
- [x] 1.3 Add `archived_at` to `StandardGiveaway`'s `$fillable`/casts (`datetime`) and add an `isArchived(): bool` helper
- [x] 1.4 Add an `archived()` state (sets `archived_at` to a fixed past instant) to `EventFactory` and `StandardGiveawayFactory`

## 2. Archive/unarchive actions

- [x] 2.1 Create `App\Actions\Events\ArchiveEventAction`: sets `status` to `Event::STATUS_CANCELLED` and `archived_at` to `now()`, regardless of current status
- [x] 2.2 Create `App\Actions\Events\UnarchiveEventAction`: sets `archived_at` to `null` only, leaving `status` untouched
- [x] 2.3 Create `App\Actions\StandardGiveaways\ArchiveStandardGiveawayAction`: same behavior as 2.1, for `StandardGiveaway`
- [x] 2.4 Create `App\Actions\StandardGiveaways\UnarchiveStandardGiveawayAction`: same behavior as 2.2, for `StandardGiveaway`
- [x] 2.5 Pest tests for all four actions: archiving from active/paused/already-cancelled all result in `status = cancelled` + `archived_at` set; unarchiving clears `archived_at` and leaves whatever `status` was there untouched

## 3. Event list: hide-by-default + toggle + per-tile action

- [x] 3.1 Add `public bool $showArchived = false` to `EventIndex`; in `render()`, apply `->when(! $this->showArchived, fn ($q) => $q->whereNull('archived_at'))` to the events query
- [x] 3.2 Add `archive(int $eventId, ArchiveEventAction $archiveEvent)` and `unarchive(int $eventId, UnarchiveEventAction $unarchiveEvent)` methods to `EventIndex`, mirroring the existing `setStatus()` method's guild-scoping + `authorize('manage', ...)` pattern
- [x] 3.3 In `event-index.blade.php`: add a "Show archived" checkbox/toggle bound to `wire:model.live="showArchived"` near the list; add an Archive button alongside the existing per-tile Activate/Pause/Cancel buttons (shown when not archived) and an Unarchive button (shown when archived) - visually distinguish an archived tile (e.g. a muted/"Archived" badge) so it's clear why it's showing when the toggle is on
- [x] 3.4 Pest/Livewire tests: default list excludes an archived event; toggling `showArchived` on includes it; `archive`/`unarchive` methods enforce guild scoping and `manage` authorization the same way `setStatus`/`delete` already do

## 4. Standard giveaway list: hide-by-default + toggle + per-tile action

- [x] 4.1 Add `public bool $showArchived = false` to `StandardGiveawayIndex`; apply the same conditional `whereNull('archived_at')` filter to its list query
- [x] 4.2 Add `archive(int $giveawayId, ArchiveStandardGiveawayAction $archiveGiveaway)` and `unarchive(int $giveawayId, UnarchiveStandardGiveawayAction $unarchiveGiveaway)` methods to `StandardGiveawayIndex`, mirroring its existing `setStatus()` method
- [x] 4.3 In `standard-giveaway-index.blade.php`: add the same "Show archived" toggle; add Archive/Unarchive per-tile buttons alongside the existing Activate/Pause/Cancel buttons, with the same archived-tile visual indicator
- [x] 4.4 Pest/Livewire tests mirroring 3.4 for the standard giveaway index

## 5. Spec alignment

- [x] 5.1 Re-read `openspec/specs/events/spec.md` and `openspec/specs/standard-giveaways/spec.md` after implementation to confirm every scenario in this change's delta specs is actually exercised by the tests added above
