## Context

See proposal.md - Why. Relevant existing pieces this design builds on:

- The design-token layer (`resources/css/app.css`'s `@theme` block, `rounded-card`/`rounded-control`/`rounded-pill`, the monochrome accent palette, the global `.rounded-card` box-shadow) is confirmed correctly built and served - verified directly against the compiled CSS output, not assumed. This change adds spacing/density conventions on top of it, not new color/radius tokens.
- Every list screen today (`GiveawayIndex`, `StandardGiveawayIndex`, `EventIndex`, `CollectionThemeIndex`, `EventRoleSetIndex`) follows the same current shape: a header with a "+ New X" toggle button, an optional inline create-form, and a `<ul class="divide-y divide-line rounded-card border border-line">` of items. Three different "how do I see more about this item" patterns exist today: Popup Giveaways/Standard Giveaway occurrences navigate to a separate full-page route (`GiveawayDashboard`, `OccurrenceDashboard`); Collection Themes/Event Role Sets always render every item's full management sub-component inline, all expanded simultaneously; Events has no detail view at all.
- `GiveawayDashboard` and `OccurrenceDashboard` are themselves full Livewire components (entrant search/filter/fulfilment UI) mounted via route model binding (`mount(Guild $guild, Giveaway $giveaway)` / `mount(Guild $guild, StandardGiveawayOccurrence $occurrence)`) - Livewire components can be embedded via `<livewire:... />` with explicit props from anywhere, not only from their own route, so reusing them inside a detail panel doesn't require duplicating their logic.
- No image reference was available at authoring time (proposal.md - Impact); this design works from the confirmed-correct existing token layer plus the user's written description of a card-tile-list-with-right-side-detail-panel pattern, common to this class of admin dashboard (e.g. an inbox/settings-style layout).

## Goals / Non-Goals

**Goals:**
- One shared structural pattern for all 5 list screens, not 5 bespoke implementations.
- Server-rendered, no client-side router/SPA framework - consistent with this codebase's "no separate SPA/API layer for the dashboard" convention.
- Existing full-page detail routes keep working unmodified - this change adds a second, in-page entry point to the same content, it doesn't remove the first.

**Non-Goals:**
- No URL/query-string sync of the selected tile (proposal.md).
- No new color or radius design tokens - this is a spacing/structure change, the color/radius system is already correct and untouched.

## Decisions

### Decision 1: A shared slotted Blade component for the responsive shell, not a new Livewire component
The responsive list-detail shell is `<x-list-detail-shell :selected="$selectedId !== null"><x-slot:list>...</x-slot:list><x-slot:detail>...</x-slot:detail></x-list-detail-shell>` (`resources/views/components/list-detail-shell.blade.php`) plus a small `<x-list-detail-empty />` placeholder (`resources/views/components/list-detail-empty.blade.php`) - a stateless Blade component with named slots, not a new Livewire component. Each existing Index component (`GiveawayIndex`, etc.) gains a single new property, `?int $selectedId = null`, and a `select(int $id)`/`deselect()` method pair; the shell component itself has no state of its own - `wire:click="deselect"` on its "Back to list" control binds to whichever Livewire component encloses it, exactly like `wire:click` calls already work inside other Blade partials (e.g. the channel picker).

**Revised during implementation**: `list-detail-shell.blade.php` originally rendered the list slot twice (once per responsive breakpoint block) to avoid a JS-driven single-markup approach - discovered during implementation that this would produce duplicate `wire:key` values within one Livewire component's DOM tree wherever the list slot contains a keyed `@foreach`, a real risk for morphdom's diffing. Revised to a single render of each slot, with `hidden lg:block` classes toggled by the `$selected` prop deciding which of the two grid columns is visible below the `lg:` breakpoint - both columns are always present in the DOM exactly once, CSS alone decides visibility per breakpoint.

**Alternative considered**: a single generic `<x-list-detail :items="..." :selected="..." detail-component="..." />` Blade *component* handling everything, including which detail Livewire component to mount, generically. Rejected - the detail panel's content differs enough per screen (an entrant-management dashboard vs. a role-set's role list vs. a theme's item list) that a fully generic "mount this Livewire component by string name with these props" abstraction would need to reconstruct exactly what each Index's own `render()` already computes, for no real benefit over five small, explicit slot-fill call sites each passing their own already-known data - consistent with this codebase's general preference for explicit, thin Livewire components over generic reusable abstractions with runtime-resolved behavior (e.g. Actions per operation rather than one parameterized mega-Action).

### Decision 2: Existing full-page detail components mount inline in the panel via explicit props, unchanged themselves
The detail panel for Popup Giveaways renders `<livewire:giveaways.giveaway-dashboard :guild="$guild" :giveaway="$selectedGiveaway" :key="'giveaway-detail-'.$selectedGiveaway->id" />` - the exact same component the `guilds.giveaways.show` route already mounts, just embedded directly instead of reached via navigation. `GiveawayDashboard`/`OccurrenceDashboard` themselves need zero changes; Livewire component mounting doesn't care whether it happened via route-model-binding or an explicit prop from a parent view.

For Collection Themes and Event Role Sets, where every item's management sub-component is shown *simultaneously* today (not one-at-a-time), the same nested components (`ManageCollectionThemeItems`, `ManageEventRoleSetRoles`) move from "always rendered once per item, all at once" to "rendered once, for whichever item is currently selected" - a strict simplification of what's already there, not new component logic.

For Events, which has no detail view today, the detail panel shows a new lightweight read-only summary (title, description, role set, recurrence, channel) - the minimum needed so Events isn't the one screen with an empty detail panel by definition; deeper occurrence-level detail continues to live at `guilds.event-occurrences.show` (`OccurrenceRoster`), linked from within that summary.

**Alternative considered**: give every Index component a lazy/deferred Livewire load (`wire:init` or Livewire's `lazy` loading) for the detail panel, to avoid mounting a heavy nested component (e.g. `GiveawayDashboard`'s entrant search) until actually selected. Rejected for v1 - the detail panel already only renders when `$selectedId` is set (Blade's `@if`, not CSS `display:none`), so unselected items' detail components are never mounted or queried in the first place; a separate lazy-loading mechanism would add complexity without avoiding any additional query cost.

### Decision 3: Style guide spacing scale is documented as a convention, not a new set of Tailwind tokens
`dashboard-style-guide`'s spacing requirements are satisfied by consistently using Tailwind's *existing* spacing scale the same way everywhere (`gap-3` between cards, `p-4` card padding, `space-y-6` between major sections - already the values used ad hoc across today's screens) rather than inventing new custom `--spacing-*` tokens in `@theme`. The "guide" is a written convention (this spec + a short comment block in `resources/css/app.css` alongside the existing token comments) that future screens are expected to follow, checked the same way `AGENTS.md`'s existing conventions are - by reading it - not by a new automated linter (out of scope; there is no CSS class usage linter in this codebase today and adding one is a separate, larger change).

**Alternative considered**: introduce custom named spacing tokens (e.g. `--spacing-card-gap`) so the scale is enforced by the token system rather than convention. Rejected - Tailwind's own default spacing scale already covers every value already in use consistently across this codebase's existing screens; a parallel custom-named scale mapping to the same numbers would add indirection without changing what anyone actually types in a `class=""` attribute.

## Risks / Trade-offs

- **[Risk]** Without a visual reference image, the resulting spacing/density is a best-effort match to the user's written description, not a verified pixel-exact match to whatever they originally had in mind. → **Mitigation**: built on the already-confirmed-correct token layer, and structured as a real screen the user can view and give direct feedback on (a live iteration loop) rather than a document trying to specify exact pixel values speculatively - the spec captures the structural/behavioral contract (cards not rows, panel not full navigation, responsive collapse), which doesn't depend on getting exact spacing right on the first pass.
- **[Risk]** (superseded - see Decision 1's "Revised during implementation") The original design would have duplicated the list slot's render across two breakpoint blocks. → **Mitigation**: resolved by rendering each slot exactly once and toggling visibility with `hidden lg:block`, which also sidesteps the duplicate-`wire:key` risk entirely rather than just accepting it.

## Migration Plan

No database migration. Purely a Blade/Livewire structural change to the 5 index components and 2 new shared partials - no Action, model, or route signature changes (existing full-page detail routes are additive-compatible, unchanged).
