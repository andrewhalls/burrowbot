## Context

`EventIndex` and `StandardGiveawayIndex` already own their selection's edit/delete state directly (`$editing`/`$editingSeries`, `toggleEdit()`/`toggleEditSeries()`, `delete()`) and swap which nested Livewire component the detail slot renders (edit form vs. read-only detail component). `GiveawayIndex` doesn't - it always renders `<livewire:giveaways.giveaway-dashboard>`, and `GiveawayDashboard` itself owns `$editing`/`toggleEdit()`/`start()`/`delete()` and swaps between its own entrant table and `<livewire:giveaways.edit-giveaway>` internally. `GiveawayDashboard` is also reachable standalone via `guilds.giveaways.show` (confirmed via a repo-wide search: nothing in the app links to that route - `dashboard-sidebar.blade.php`/`dashboard-topbar.blade.php` only reference its route *name* for breadcrumb/active-nav labeling, in case someone lands there directly).

## Goals / Non-Goals

**Goals:**
- One header row per screen, holding "+ New X" and the selection's contextual actions together.
- Create form renders in the detail panel, matching how edit/view already work.
- Popup Giveaway's structure matches Events/Standard Giveaways: the Index component owns selection-driven state, the nested detail component is a plain display component.

**Non-Goals:**
- Preserving Start/Edit/Delete on the standalone `guilds.giveaways.show` page - see Decision 1.

## Decisions

### Decision 1: `GiveawayDashboard` becomes a plain display component; Start/Edit/Delete move to `GiveawayIndex` only
Mirrors `OccurrenceDashboard`/`occurrence-roster` (StandardGiveaway/Event's nested detail components), which already carry no action buttons of their own - all lifecycle actions live in their parent Index component. Moving Start/Edit/Delete out of `GiveawayDashboard` and into `GiveawayIndex` makes Popup Giveaway match that shape and puts its actions in the shared header row like the other two screens, with no double-header stacking.

The trade-off: the standalone `guilds.giveaways.show` route (`GiveawayDashboard` outside `GiveawayIndex`) loses the ability to start/edit/delete a giveaway directly from that page - only the read-only entrant view remains there. Accepted because that route is already unlinked from the rest of the app (confirmed above); `dashboard-list-detail-layout`'s existing "direct routes... remain reachable" requirement is about *detail content* staying reachable by URL, which it still is - it was never a guarantee that every action available in the list-detail context is also available standalone. Managing a popup giveaway now happens the same way as managing an event or standard giveaway: from its guild's list screen.

**Alternative considered**: keep `GiveawayDashboard`'s own header for the standalone case, and additionally add Start/Edit/Delete to `GiveawayIndex`'s header for the embedded case, toggled by a new `$showActions` prop to avoid double buttons when embedded. Rejected - this keeps two parallel copies of the same three action-triggering methods (one per component) in sync forever, for a route nothing links to; the maintenance cost isn't worth preserving functionality on a dead end.

### Decision 2: Create-vs-select mutual exclusion lives in each Index component's own `select()`/create-toggle methods, not a shared trait
Each Index component already differs slightly in what else it resets on selection (`EventIndex` also clears `$selectedOccurrenceId`; `StandardGiveawayIndex` also clears `$editingSeries`). Adding "and close the create form" / "and clear the selection" to each screen's existing `select()` and a new `toggleCreateForm()` method is a one-line addition per method - not worth a shared trait for five call sites that already have a proven pattern (each Index component is already a small, independent class with its own selection-reset logic).

### Decision 3: Auto-select the newly created item via the Create component's already-dispatched id
`CreateEvent`/`CreateGiveaway`/`CreateStandardGiveaway` already dispatch `{x}-created` with the new row's id; `CreateCollectionTheme`/`CreateEventRoleSet` currently dispatch no id, so they gain one (a one-line addition, no behavior change to the dispatch itself otherwise). Each Index component's existing `#[On('{x}-created')]` listener gains that id as a parameter and sets `$selectedId` from it, alongside its existing `$showCreateForm = false`.

## Risks / Trade-offs

- [`x-list-detail-shell`'s `:selected` prop currently means "an item is selected"; it now also needs to mean "the create form is open" for the narrow-screen collapse to show the detail panel instead of the list while creating] → Each Index Blade view's `:selected="..."` expression becomes `$selectedX !== null || $showCreateForm`, a mechanical one-line change per screen; no change to `x-list-detail-shell` itself.
