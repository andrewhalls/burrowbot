## 1. Delete Popup Giveaway

- [x] 1.1 `DeleteGiveawayDraftAction`: reject unless `isDraft()`, otherwise `delete()`
- [x] 1.2 `GiveawayDashboard::delete()`: authorize `manage`, call the action, redirect to `guilds.giveaways.index`
- [x] 1.3 `giveaway-dashboard.blade.php`: "Delete" button next to Edit/Start (draft-only), with confirmation
- [x] 1.4 Pest tests: deleting a draft removes it; deleting an active/closed giveaway is rejected and leaves it untouched

## 2. Delete Standard Giveaway

- [x] 2.1 `DeleteStandardGiveawayAction`: reject if `occurrences()->whereIn('status', [posted, closed])->exists()`, otherwise `delete()`
- [x] 2.2 `StandardGiveawayIndex::delete()`: authorize `manage`, call the action, deselect and refresh
- [x] 2.3 `standard-giveaway-index.blade.php`: "Delete" button in the detail panel (alongside "Edit series"), with confirmation
- [x] 2.4 Pest tests: deleting a series with zero/only-scheduled occurrences removes it (and its occurrences); deleting one with a posted or closed occurrence is rejected and leaves everything untouched

## 3. Delete Event

- [x] 3.1 `DeleteEventAction`: reject if `occurrences()->where('status', posted)->exists()`, otherwise `delete()`
- [x] 3.2 `EventIndex::delete()`: authorize `manage`, call the action, deselect and refresh
- [x] 3.3 `event-index.blade.php` / summary: "Delete" button, with confirmation
- [x] 3.4 Pest tests: deleting a series with zero/only-scheduled occurrences removes it; deleting one with a posted occurrence is rejected and leaves everything untouched

## 4. Verification

- [x] 4.1 Full Pest suite passes
- [x] 4.2 `npm run build` succeeds
