## Why

The dashboard's design tokens (dark icon rail, breadcrumb topbar, monochrome accent, pill buttons, card shadows) are correctly implemented and confirmed working, but every list screen (Events, Popup Giveaways, Standard Giveaways, Collection Themes, Event Role Sets) still renders as a plain divided list with thin row separators - sparse, low-density, and inconsistent in how "detail" is reached (Popup Giveaways/Standard Giveaway occurrences navigate to a separate full page; Collection Themes/Event Role Sets always show every item's full management UI inline at once; Events has no detail view at all). There is also no written style guide, so this inconsistency will keep recurring as new screens get built.

## What Changes

- Every guild-scoped list screen adopts a shared card-based tile layout: list items render as distinct visually-separated cards (not thin-divider rows), with consistent spacing/density.
- Every list screen adopts a shared master-detail structure: a list of tiles on the left, a detail panel on the right that opens when a tile is selected (showing an empty-state placeholder when nothing is selected). Below a responsive breakpoint, the detail panel replaces the list entirely (with a back control) rather than showing both side by side.
- A written style guide capability documents the durable visual system (spacing scale, card treatment, list density, empty states) so it's a checkable reference for all future dashboard work, not tribal knowledge.
- **BREAKING** (internal navigation only, not routes): Popup Giveaways' and Standard Giveaways' existing "Manage" full-page navigation is replaced by the in-page detail panel for the index screens' own interaction; the underlying full-page routes (`guilds.giveaways.show`, `guilds.standard-giveaway-occurrences.show`) are unchanged and continue to work for direct/bookmarked links.

## Capabilities

### New Capabilities
- `dashboard-style-guide`: the durable visual design system for the dashboard - spacing scale, card/tile treatment, list density, empty-state presentation - as a reference every list/detail screen must follow.
- `dashboard-list-detail-layout`: the reusable structural pattern (card-tile list + right-side detail panel, collapsing to a single full-width column on narrow screens) used by every guild-scoped list view.

### Modified Capabilities
None - no existing capability's requirement text mandates a specific page-vs-panel access mechanism for a list's detail view (verified against `giveaway-admin-dashboard`, `collection-themes`, `event-role-sets`, `standard-giveaway-occurrences`); this is purely a new structural/visual layer those capabilities' existing behavior renders through.

## Impact

- **Affected code**: `GiveawayIndex`, `StandardGiveawayIndex`, `EventIndex`, `CollectionThemeIndex`, `EventRoleSetIndex` and their Blade views (all five adopt the shared list-detail structure); a new shared Blade component/partial pair for the tile list and detail-panel shell; `resources/css/app.css` gains any additional spacing/density tokens the style guide formalizes.
- **No changes** to any Action, model, validation rule, or the underlying full-page detail routes/components (`GiveawayDashboard`, `OccurrenceDashboard`) - they gain a second entry point (inline in the detail panel) alongside their existing direct route, not a replacement.
- **Visual reference**: built from the dashboard design language already confirmed correct and working (dark icon rail, breadcrumb topbar, monochrome accent tokens, pill buttons, `rounded-card`/`rounded-control` radii, card shadow) plus the user's description of a card-tile-list-with-right-side-detail-panel pattern - not a pixel-exact match against a specific external reference image, since none was available at authoring time.

## Non-goals

- No deep-linking/URL sync for which tile is currently selected in the detail panel - selection is in-memory Livewire component state for v1; reachable directly via the existing full-page routes regardless.
- No change to entrant/signup/fulfilment business logic, search, or filtering - purely the shell each screen renders inside.
- No animation/transition polish beyond what Tailwind's default utilities provide - this change is about structure and density, not motion design.
