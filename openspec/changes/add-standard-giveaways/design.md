## Context

See `proposal.md` - Why/What Changes for motivation and scope. This builds directly on two existing pieces of the system rather than inventing new architecture:

- The **Events** change's series/occurrence pattern (`Event`/`EventOccurrence`, RRULE via `simshaun/recurr`, occurrence snapshotting so mid-series edits don't retroactively change already-generated occurrences, and the generate→post two-step scheduled-command pair).
- The original giveaway platform's **duration + authoritative expiry** pattern (`Giveaway.duration_minutes`/`ends_at`, the row-locked transaction, and `AssignRandomItem`'s no-repeat-until-exhausted fairness rule) and the bot↔Laravel architecture (Laravel owns all state/rules; the bot only relays Discord events and executes outbound actions Laravel requests).

This is the first capability that needs data Burrow doesn't otherwise track: whether a member currently holds a given Discord role, and whether they're currently boosting the guild. Burrow does not sync a member's roles or boost status anywhere (`discord_members` only stores username/avatar) - see Decision 1.

## Goals / Non-Goals

**Goals:**
- Fix how eligibility (booster/role) is captured and checked without Laravel ever talking to Discord directly.
- Fix the occurrence lifecycle: post → stay open until an end time → close and draw N winners → announce, reusing Events' generation machinery and the original giveaway's expiry-authority pattern rather than inventing a third temporal model.
- Fix the data model so editing an in-flight recurring giveaway's prize items/restrictions never changes an already-generated occurrence (same guarantee Events gives).
- Fix the winner-draw and multi-item-assignment algorithms precisely enough to unit test.

**Non-Goals:**
- Syncing or storing a member's full role list or boost status independently of an entry attempt (see Decision 1's alternatives).
- Re-verifying eligibility at draw time (see Decision 4) - v1 checks once, at entry.
- A UI for building the prize-item search/multi-select or the recurrence picker beyond what Events already built (the recurrence picker is reused as-is; the prize-item picker is new but follows the same searchable-list UI pattern already used for member search).

## Decisions

### 1. Eligibility data comes from the bot, at the moment of entry - not synced ahead of time
**Decision:** `POST /internal/standard-giveaway-occurrences/{id}/entries` accepts `discord_role_ids: string[]` and `is_boosting: boolean` in the request body, supplied by the bot straight from the Discord interaction payload (`interaction.member.roles` and `interaction.member.premium_since !== null`). Laravel checks the occurrence's snapshotted `requires_booster` flag and required-role-id list against exactly that payload, in the same locked transaction that creates the entry. Nothing about a member's roles or boost status is stored outside of what's needed to log why a specific entry was accepted (the check result, not the raw role list).

**Alternatives considered:**
- *Laravel maintains a synced table of member role membership* - rejected: would require the bot to stream every role-change event for every member in every guild (far more gateway traffic and a new sync surface) to support a feature that only ever needs this data at the instant of an entry click. Massive scope increase for no behavioral benefit in v1.
- *Bot checks eligibility itself and only forwards accepted entries* - rejected: violates the established principle that Laravel owns all business rules and the bot never makes decisions, only relays (see `discord-bot-gateway`'s existing requirements) - would make the restriction rules untestable via Pest and duplicate logic across two languages.

### 2. Occurrence temporal model: reuse "post, then run for a duration" from the original giveaway
**Decision:** `standard_giveaways.duration_minutes` is how long each occurrence stays open once posted (semantically identical to the original `Giveaway.duration_minutes`, just typically much larger - hours to weeks). An occurrence has three timestamps:
- `scheduled_post_at` - when it's due to be generated/posted (from RRULE expansion, or "now" for a one-off). This is the dedup key for generation, exactly like `EventOccurrence.scheduled_start_at`.
- `posted_at` - stamped when the bot's post is acked.
- `ends_at` - computed as `posted_at + duration_minutes` at posting time (not at generation time), so a giveaway's duration always measures from when entrants can actually see it, not from whenever the generation job happened to run.

Two scheduled commands, mirroring Events' pair and the original giveaway's close job:
- `standard-giveaways:generate-occurrences` (hourly) - same RRULE-expansion-into-a-window logic as `events:generate-occurrences`, reused via the same `ExpandRecurrenceRule` class.
- `standard-giveaways:post-due-occurrences` (every minute) - posts every `scheduled` occurrence (v1 has no post lead time, same default as Events), stamping `posted_at` and computing `ends_at`.
- `standard-giveaways:close-expired` (every minute) - finds `posted` occurrences whose `ends_at` has passed, closes them, runs the draw (Decision 3), and enqueues the "announce winners" outbound action. Named to match `giveaways:close-expired`'s convention.

### 3. Draw algorithm
Implemented as a single DB transaction (`CloseAndDrawStandardGiveawayOccurrenceAction`), row-locking the occurrence exactly like `JoinGiveawayAction` and `SignUpForEventRoleAction` do, so a close can never race with a very-last-moment entry:

1. Lock the occurrence row. If it's not `posted` or `ends_at` hasn't passed, no-op (idempotent - the scheduled command may see the same row twice across ticks).
2. Flip status to `closed`.
3. Load all `standard_giveaway_entries` for the occurrence as the eligible pool (eligibility was already enforced at entry time - see Decision 4 for why this pool isn't re-filtered).
4. Draw `min(winner_count, count(entries))` distinct entries uniformly at random via a pure `DrawRandomWinners` function (injectable randomizer, same testability pattern as `AssignRandomItem`).
5. For each drawn entry, assign a prize item using `AssignRandomItem` unmodified - called once per winner, accumulating "already-won" item ids across the winners of *this occurrence only*, exactly as it already accumulates across entrants of one pop-up giveaway.
6. Insert `standard_giveaway_winners` rows (entry, item, drawn_at) and enqueue the outbound action to announce them.

### 4. Eligibility is checked once, at entry - not re-verified at closing
**Decision:** A member's `discord_role_ids`/`is_boosting` snapshot is validated only at the moment they enter (Decision 1). If their roles change afterward (they unboost, a role is removed), their entry stands and they remain in the eligible pool the draw pulls from.

**Rationale:** Re-checking at close time would require the bot to fetch fresh member state for every entrant during the close job - a new bot↔Laravel round-trip with no scheduled-command equivalent to the interaction-driven pattern everything else uses, and a window where the "authoritative" answer depends on exactly when the check runs relative to a role change. Checking once, at a clearly-defined moment (the entry click), is simpler, matches how the *entry* cutoff itself is already authoritative-at-a-point-in-time (`standard-giveaway-entries` - "Entry rejected once closed, enforced server-side"), and is the behavior a reasonable admin would expect ("you were eligible when you entered").

## Risks / Trade-offs

- **A member could lose eligibility between entering and winning** (Decision 4) - accepted trade-off, documented above; a future change could add re-verification if this proves to matter in practice.
- **JSON snapshot columns instead of join tables for an occurrence's prize items/required roles** - `standard_giveaway_occurrences.prize_item_ids`/`required_role_ids` are JSON arrays rather than pivot tables, unlike the *giveaway*-level `standard_giveaway_prize_items`/`standard_giveaway_required_roles` (which remain real pivot tables since the dashboard needs to query/manage them). This keeps the occurrence table's immutable, write-once snapshot simple without two extra join tables and models that are never updated after creation - consistent with how `EventOccurrence` snapshots scalar columns rather than re-deriving them from a live join.
- **Reusing `AssignRandomItem` across winners of one draw, not across a whole giveaway's history** - a prize item can repeat across different occurrences of the same recurring giveaway (each occurrence's "already-won" set starts empty) - this is intentional and matches "Independent entrant list per occurrence" already required.

## Migration Plan

Additive only - six new tables (`standard_giveaways`, `standard_giveaway_prize_items`, `standard_giveaway_required_roles`, `standard_giveaway_occurrences`, `standard_giveaway_entries`, `standard_giveaway_winners`), no changes to existing giveaway, event, or collection-theme tables. `discord_outbound_actions` gains two more `type` enum values (already nullable-FK-friendly after the Events change) plus a nullable `standard_giveaway_occurrence_id` column, exactly as it gained `event_occurrence_id`. Deploy order: migrate, deploy Laravel with the three new scheduled commands registered, deploy the updated bot process (new outbound action types + new interaction handler), then start creating standard giveaways.
