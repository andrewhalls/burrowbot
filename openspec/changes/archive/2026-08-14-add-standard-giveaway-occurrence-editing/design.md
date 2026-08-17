## Context

`StandardGiveawayOccurrence.description` and `.prize_item_ids` are already plain columns on the occurrence itself (the latter a JSON-cast array, not a `HasMany` relation like the series' `prizeItems()`) - snapshotted at generation time from the series template and never touched again except by the series-level edit path (which only affects occurrences generated *after* the edit). Editing one occurrence directly is therefore a simple column overwrite, not a re-sync like `UpdateStandardGiveawayAction`'s prize-item handling.

`StandardGiveawayIndex` currently shows exactly one occurrence per series - whichever has the latest `scheduled_post_at` across ALL statuses (`orderByDesc('scheduled_post_at')->first()`), with no way to see or pick a different one. That auto-pick behavior is unchanged by this proposal; this adds an explicit way to browse to and edit a *specific* upcoming occurrence alongside it.

## Goals / Non-Goals

**Goals:**
- Let an admin find and edit a specific upcoming (not-yet-posted) occurrence's description/prize items.
- Reuse the existing prize-item search/chip UI pattern (`EditStandardGiveaway`) rather than inventing a new one.

**Non-Goals:**
- Redesigning how `StandardGiveawayIndex` picks its "default" shown occurrence - out of scope, not requested, and changing it risks disrupting the existing view-a-posted-occurrence workflow.

## Decisions

### Decision 1: Scoped to description + prize items only
The user's own ask ("change the item and description each week in advance") sets the scope. Every other occurrence field (`title`, `image_path`, `channel_id`, `posting_mode`, `requires_booster`, `winner_count`, `duration_minutes`) stays series-wide, edited only via `EditStandardGiveaway` (affecting occurrences generated after that edit). Keeping this form minimal avoids blurring "this specific week is different" (occurrence edit) with "the series changed" (series edit) - if an admin wants to change the channel or winner count going forward, that's still unambiguously a series edit.

### Decision 2: A new small Livewire component, not a mode on `OccurrenceDashboard`
`OccurrenceDashboard` is a plain display component (winners/entrants tables) - it never owned editing state even before this session's list-detail-header refactor moved Edit/Delete out of `GiveawayDashboard` into its parent Index component for the same reason. `EditStandardGiveawayOccurrence` is a new sibling component, and `StandardGiveawayIndex` (not `OccurrenceDashboard`) owns the "which occurrence, if any, is being edited" state - consistent with that same architecture.

### Decision 3: "Upcoming occurrences" list lives in `StandardGiveawayIndex`'s detail panel, above the existing occurrence content
A guild admin selects a series, sees (as today) its default occurrence's dashboard - now with a compact "Upcoming occurrences" list above it when the series has any `scheduled` occurrences, each row showing its date and current prize item(s)/description preview with an Edit action. Clicking Edit swaps the detail panel to `EditStandardGiveawayOccurrence` for that occurrence (mirroring how `toggleEditSeries`/`toggleCreateForm` already swap the panel's content). Listed ordered soonest-first (`orderBy('scheduled_post_at')`), capped at 10 - the same "small bounded list, not a full paginated browser" scale as `event-summary.blade.php`'s "Recent occurrences" (capped at 5).

## Risks / Trade-offs

- [An occurrence could post (cron `standard-giveaways:post-due-occurrences` runs every minute) between an admin opening the edit form and saving it] → `UpdateStandardGiveawayOccurrenceAction` re-checks `status === scheduled` at save time (not just when the list/form was rendered), so a save that loses this race is rejected with a clear error rather than silently editing a live post.
