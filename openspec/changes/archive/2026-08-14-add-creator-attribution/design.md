## Context

`Giveaway`, `StandardGiveaway`, and `Event` have no notion of who created them. The acting admin is always known at creation time, though: `CreateGiveaway`, `CreateStandardGiveaway`, and `CreateEvent` (the three Livewire components) each call `$this->authorize('manage', $this->guild)` at the top of `save()` before their respective `Create*Action` runs, which guarantees `Auth::user()` is a non-null, guild-authorized `User` by that point - the same precondition `GiveawayDashboard` already relies on when it passes `Auth::user()` into `FulfillGiveawayEntryAction`.

`add-giveaway-and-event-editing` (a separate, already-planned, not-yet-applied change) also touches `Event creation`'s requirement text (adding an image field) and adds Edit forms for all three domains. This change and that one both add fields at creation time but are otherwise independent - order of application doesn't matter functionally, since each is a straightforward additive column plus a constructor-argument addition, not a structural change either depends on.

## Goals / Non-Goals

**Goals:**
- Record the creating admin on all three top-level records at creation time.
- Show "Created by {name}" on each domain's list tile and detail view.

**Non-Goals:**
- No creator tracking below the series/giveaway level (occurrences, entries, signups, themes) - out of scope per the proposal.
- No reassignment/editing of the creator after the fact.
- No backfill for existing rows.

## Decisions

**Decision 1: Nullable FK, not required.**
`created_by_user_id` is nullable on all three tables. Existing rows get `null` (no backfill, per Non-goals) and any future creation path that doesn't go through the three Livewire components (none exist today) still succeeds. Alternative considered - a `NOT NULL` column with a migration-time backfill to some placeholder "system" user - rejected as inventing false data for a "who did this" field, which is worse than admitting it's unknown.

**Decision 2: Pass `Auth::user()` explicitly into the Create actions, not resolved inside them.**
`CreateGiveawayAction`/`CreateStandardGiveawayAction`/`CreateEventAction` are already plain, framework-agnostic classes with no knowledge of the request/auth context (their tests construct them directly and call `execute()` with explicit arguments). Keeping that shape - accepting `?User $createdBy` as a new parameter rather than reaching for `Auth::user()` internally - keeps them equally easy to unit-test and consistent with how `FulfillGiveawayEntryAction` already takes its acting `User` as an explicit argument rather than resolving it itself.

**Decision 3: Display via a `creator()` BelongsTo, rendered as the user's `name`.**
Each model gets a `creator(): BelongsTo<User, $this>` relation. Blade views render `$model->creator?->name`, falling back to nothing (no placeholder text) when null, consistent with Decision 1 - a record either has a known creator or the UI simply omits the line, it never says "Unknown" or similar filler.

## Risks / Trade-offs

- [`add-giveaway-and-event-editing` also modifies the `events` capability's "Event creation" requirement text] → Not a functional conflict (different fields, additive), and OpenSpec's archive-time sync is a semantic merge against the then-current main spec, so applying/archiving either change first and the other second both converge correctly - no forced ordering required.
- [A guild admin's dashboard account could later be deleted while old records still reference it] → `created_by_user_id` uses `nullOnDelete()` on its foreign key, matching how an orphaned reference should degrade (falls back to "no creator shown", not a broken relation or cascade-deleted giveaway).
