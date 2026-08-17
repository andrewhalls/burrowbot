## 1. Backend

- [x] 1.1 `UpdateStandardGiveawayOccurrenceAction`: reject unless `status === scheduled`, otherwise overwrite `description` and `prize_item_ids` directly
- [x] 1.2 Pest tests: editing a scheduled occurrence updates it and leaves the series/other occurrences untouched; editing a posted or closed occurrence is rejected and leaves it untouched

## 2. Edit form

- [x] 2.1 `EditStandardGiveawayOccurrence` Livewire component: description field + prize-item search/chip UI (mirrors `EditStandardGiveaway`), pre-filled from the occurrence, calls the new action on save
- [x] 2.2 `edit-standard-giveaway-occurrence.blade.php`
- [x] 2.3 Pest/Livewire tests: pre-fills from the occurrence; saves description/prize item changes; denies mounting for a guild the user does not admin

## 3. Wiring into StandardGiveawayIndex

- [x] 3.1 `StandardGiveawayIndex`: `$editingOccurrenceId`, `toggleEditOccurrence(int $occurrenceId)` (authorize, verify the occurrence belongs to the selected series and is scheduled), `#[On('standard-giveaway-occurrence-updated')]` to close the form; clear `$editingOccurrenceId` alongside the existing selection-reset points (`select()`, `toggleCreateForm()`, `toggleEditSeries()`)
- [x] 3.2 `render()`: pass the selected series' `scheduled` occurrences (ordered soonest-first, capped at 10) to the view
- [x] 3.3 `standard-giveaway-index.blade.php`: "Upcoming occurrences" list above the existing occurrence content, each row showing date + prize item(s)/description preview + an Edit action; editing swaps the detail panel to `EditStandardGiveawayOccurrence`
- [x] 3.4 Pest/Livewire tests: upcoming occurrences list appears only when the series has scheduled occurrences; toggling edit swaps the detail panel; selecting a different series/opening create/edit-series closes an open occurrence edit

## 4. Verification

- [x] 4.1 Full Pest suite passes
- [x] 4.2 `npm run build` succeeds
