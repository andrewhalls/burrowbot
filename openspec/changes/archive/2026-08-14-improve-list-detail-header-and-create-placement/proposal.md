## Why

Two structural issues in every list-detail screen, per direct user feedback: (1) the selected item's actions (Edit/Edit series/Start/Delete) render in their own row inside the detail panel, visually disconnected from the page's own header row (which holds the "+ New X" button) - two stacked header-like bars instead of one; (2) clicking "+ New X" inserts the create form as a block between the page header and the list-detail area, pushing the whole list-detail area down the page, instead of using the detail panel (the same space Edit/View already use).

## What Changes

- Every list-detail screen's top header row becomes the single place for both "+ New X" and the selected item's contextual actions (Edit/Edit series/Start/Delete) - shown together, right-aligned, only the actions relevant to the current selection/status appearing.
- "+ New X" now opens the create form inside the detail panel (replacing whatever it would otherwise show - summary, edit form, or empty state) instead of inserting a separate block above the list-detail area. Selecting a tile while the create form is open cancels the create form; opening the create form while a tile is selected deselects it - the detail panel always shows exactly one thing.
- On successful creation, the new item is selected automatically, so the detail panel shows it immediately instead of reverting to the empty state.
- Popup Giveaway's dashboard (`GiveawayDashboard`) loses its own Start/Edit/Delete header row - that logic moves to `GiveawayIndex`, matching how `EventIndex`/`StandardGiveawayIndex` already own their Edit/Delete/editing state rather than the nested detail component owning it. This also fixes the double-header look popup giveaways had (an outer "Popup giveaways / + New giveaway" bar and an inner "Entrants / Edit / Start / Delete" bar).

## Capabilities

### Modified Capabilities

- `dashboard-list-detail-layout`: the create form's placement and the selected item's action placement become explicit, checkable conventions every list-detail screen must follow.

## Impact

- Multi-guild scoping: unaffected - purely a rearrangement of already-guild-scoped, already-authorized UI.
- All 5 list-detail Index components (`EventIndex`, `GiveawayIndex`, `StandardGiveawayIndex`, `CollectionThemeIndex`, `EventRoleSetIndex`) and their Blade views: header row gains contextual action buttons; detail slot gains a create-form branch; `select()` cancels an open create form and vice versa; the `x-list-detail-shell` `:selected` prop now also accounts for the create form being open (so the narrow-screen collapse behaves correctly while creating).
- `GiveawayDashboard` loses its Start/Edit/Delete header row and the `$editing`/`toggleEdit()`/`start()`/`delete()` state it drives; `GiveawayIndex` gains that state instead, mirroring `EventIndex`/`StandardGiveawayIndex`, and its detail slot swaps between `<livewire:giveaways.edit-giveaway>` and `<livewire:giveaways.giveaway-dashboard>` the same way the other two screens swap between their edit form and detail component.
- `CreateCollectionTheme`/`CreateEventRoleSet` dispatch events gain the new row's id (mirroring the other three Create components), so their Index components can auto-select it.

## Non-goals

- No change to what each screen's create/edit forms contain - only where the form renders and how selection state interacts with it.
- No change to the underlying create/edit/delete/start business logic (actions, validation, authorization) - this is a UI placement change only.
- The standalone `guilds.giveaways.show` route (`GiveawayDashboard` outside `GiveawayIndex`) keeps working for viewing entrants - it simply no longer offers Start/Edit/Delete directly on that page (unlinked from the rest of the app already; managing a giveaway now happens via its guild's giveaway list, consistent with events/standard giveaways).
