## 1. Popup Giveaway restructure (design.md Decision 1)

- [x] 1.1 `GiveawayDashboard`: remove `$editing`, `toggleEdit()`, `start()`, `delete()`, and the header row that renders them; keep the entrant table/search/filter as its whole template
- [x] 1.2 `GiveawayIndex`: add `$editing`, `toggleEdit()`, `start()`, `delete()` (moved from `GiveawayDashboard`, operating on `$selectedGiveaway`)
- [x] 1.3 `giveaway-index.blade.php` detail slot: `@if ($editing) <livewire:giveaways.edit-giveaway> @else <livewire:giveaways.giveaway-dashboard> @endif` (mirrors event-index.blade.php/standard-giveaway-index.blade.php)
- [x] 1.4 Update/move existing `GiveawayDashboard` edit/start/delete Pest tests to `GiveawayIndexTest`

## 2. Header row + create-in-detail-panel, all 5 screens

- [x] 2.1 `EventIndex`, `GiveawayIndex`, `StandardGiveawayIndex`, `CollectionThemeIndex`, `EventRoleSetIndex`: replace the `$toggle('showCreateForm')` button with a `toggleCreateForm()` method that also clears the current selection when opening; each `select()` method also closes the create form
- [x] 2.2 Each Index Blade view: move the selected item's contextual actions (Edit/Edit series/Start/Delete) into the top header row next to "+ New X"; detail slot gains `@if ($showCreateForm) <livewire:x.create-x> @elseif (...) ...existing... @endif`; `x-list-detail-shell`'s `:selected` prop becomes `$selectedX !== null || $showCreateForm`
- [x] 2.3 `CreateCollectionTheme`/`CreateEventRoleSet`: dispatch their created row's id (mirroring the other three Create components)
- [x] 2.4 Each Index component's `#[On('{x}-created')]` listener: accept the new id, set it as the selection
- [x] 2.5 Pest/Livewire tests per screen: opening create closes any selection; selecting a tile closes an open create form; submitting create selects the new item; contextual actions appear/disappear in the header based on selection state

## 3. Verification

- [x] 3.1 Full Pest suite passes
- [x] 3.2 `npm run build` succeeds
