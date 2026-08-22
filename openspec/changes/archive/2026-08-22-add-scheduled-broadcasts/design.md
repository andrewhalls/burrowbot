## Context

See `proposal.md` for motivation and scope. This builds directly on the series/occurrence pattern already established twice in this codebase:

- **Events** (`Event`/`EventOccurrence`): the RRULE recurrence engine (`simshaun/recurr` via `BuildRecurrenceRule`/`ParseRecurrenceRule`/`ExpandRecurrenceRule`), the generate-into-a-rolling-window-then-post-when-due two-scheduled-command pair, occurrence snapshotting so mid-series edits never retroactively change already-generated occurrences, and the active/paused/cancelled + archive/unarchive status lifecycle.
- **Standard giveaways**: the closest prior art for a message template with placeholders (`congrats_message_template`, resolved at send time from winner/prize/claim data) and for reusing the recurrence engine for something that isn't Events itself.

Broadcasts are strictly simpler than both: there's no signup roster (`event-occurrences`) and therefore no waitlist or waitlist-promotion concept at all, no entrant/eligibility/draw machinery (`standard-giveaway-occurrences`), and no "ends"/expiry concept - an occurrence is generated, posted once, and done. The only piece of the Events machinery this change touches is recurrence-rule expansion and the generate-then-post scheduling shape (Decisions 2-3 below); signups, roles, and waitlists are untouched.

## Goals / Non-Goals

**Goals:**
- Reuse the existing RRULE recurrence engine and generate/post scheduled-command pattern unmodified - no new scheduling mechanism.
- Define a small, fixed, documented placeholder set resolved at post time (not generation time, not template-authoring time), so `{{date}}`/`{{time}}`/`{{next_occurrence_date}}` are always accurate to when the message actually appears in Discord.
- Keep the data model and dashboard screen consistent with `events`/`standard-giveaways` (status lifecycle, archive/unarchive, editing-only-affects-future-occurrences, deletion-blocked-once-posted) so staff already familiar with those screens don't have to learn a new mental model.

**Non-Goals:**
- Per-recipient personalization (see `proposal.md` Non-goals) - placeholders describe the broadcast/occurrence/guild, never an individual member.
- A general-purpose templating language - placeholders are a fixed, literal `{{token}}` substitution list, not conditionals, loops, or arbitrary expressions.
- A UI for building the recurrence picker beyond what Events already built - reused as-is.

## Decisions

### 1. Placeholders resolve at post time, from a pure substitution function
**Decision:** A new pure class, `RenderBroadcastMessage`, takes the occurrence's snapshotted `message_template` string plus a small resolved-values struct (`guild_name`, `channel_id` → rendered as a `<#channel_id>` Discord channel mention, `posted_at` in the broadcast's `recurrence_timezone` for `{{date}}`/`{{time}}`, and an optional next-occurrence date) and returns the final message string via literal `{{token}}` replacement. It runs inside `broadcasts:post-due-occurrences`, immediately before requesting the bot post the message - never at generation time, and never persisted pre-rendered, so the stored occurrence always keeps the template, and the rendered text is computed fresh at the moment of posting.

Supported placeholders (v1, fixed set):
| Placeholder | Resolves to |
|---|---|
| `{{guild_name}}` | The guild's Discord server name |
| `{{channel}}` | A mention of the channel the occurrence is posted to |
| `{{date}}` | The date the occurrence is posted, in the broadcast's configured timezone |
| `{{time}}` | The time the occurrence is posted, in the broadcast's configured timezone |
| `{{next_occurrence_date}}` | The next scheduled occurrence's date for a recurring broadcast (see Decision 3); empty string for a one-off broadcast or the last occurrence of a bounded recurrence |

An unrecognized `{{...}}` token (typo, or a placeholder from a future version) is left in the output as literal text rather than raising an error or dropping it silently - consistent with "don't fail a scheduled post over a cosmetic template mistake," and matches the non-erroring intent already implied by `standard-giveaways`' "Template using no placeholders" scenario.

**Alternatives considered:**
- *Render and store the final text at generation time* - rejected: for a recurring broadcast generated hours or days ahead of its post time (same rolling-window generation as Events), `{{date}}`/`{{time}}`/`{{next_occurrence_date}}` would be stale by the time the message actually appears. Mirrors why `standard-giveaway-occurrences` computes `ends_at` from `posted_at`, not `scheduled_post_at` (that change's Decision 2).
- *A general templating engine (Blade, Twig, Handlebars)* - rejected as scope creep: v1 needs literal placeholder substitution, not logic; pulling in a templating dependency for five fixed tokens isn't justified.

### 2. Data model: two tables, mirroring `events`/`event_occurrences`

`broadcasts`: `guild_id`, `title`, `message_template` (text), `channel_id`, `status` (`active`/`paused`/`cancelled`), `archived_at` (nullable), `recurrence_rule` (nullable RRULE string), `recurrence_start_at`, `recurrence_timezone`, `created_by` (nullable FK to the creating admin), timestamps.

`broadcast_occurrences`: `broadcast_id`, snapshotted `message_template` and `channel_id` (so a mid-series edit never changes an already-generated occurrence, same guarantee as `EventOccurrence`), `scheduled_post_at`, `posted_at` (nullable until posted), `discord_message_id` (nullable until posted), `status` (`scheduled`/`posted`), `unique(broadcast_id, scheduled_post_at)` as the generation dedup key (same pattern as `EventOccurrence`/`StandardGiveawayOccurrence`).

No `ends_at`, no entrant/roster tables, no eligibility/draw columns - a broadcast occurrence has nothing analogous to close.

### 3. Occurrence lifecycle: two scheduled commands, no third "close" job

Mirrors Events' pair exactly, minus the close step (there's nothing to close):
- `broadcasts:generate-occurrences` (hourly) - identical rolling-window RRULE expansion via the unmodified `ExpandRecurrenceRule`, snapshotting the broadcast's current `message_template`/`channel_id` into the occurrence at generation time.
- `broadcasts:post-due-occurrences` (every minute) - posts every `scheduled` occurrence whose `scheduled_post_at` has arrived: resolves placeholders (Decision 1), requests the bot post the message via a new `post_broadcast_message` `discord_outbound_actions` type, and on ack stamps `posted_at`/`discord_message_id` and flips status to `posted`.

`{{next_occurrence_date}}` (Decision 1) is computed inside `post-due-occurrences` by calling `ExpandRecurrenceRule` for a window starting just after the occurrence's own `scheduled_post_at`, taking the first result (or empty string if none - one-off broadcast, or a bounded recurrence that has ended). This reuses the same pure function generation already depends on; no new recurrence-expansion logic.

### 4. Editing, deleting, archiving: same guarantees as Events/standard giveaways
- Editing a broadcast's title, message template, channel, or recurrence rule affects only occurrences generated after the edit (per-occurrence snapshotting, Decision 2) - identical wording/guarantee to `events`' "Editing an event series only affects future occurrences."
- Deleting a broadcast series is allowed only while none of its occurrences have posted; rejected once any occurrence is `posted`, identical to `events`' delete-blocked rule.
- Archiving sets status to `cancelled` (stopping future generation) and marks the series archived; unarchiving clears only the archived marker. Archived broadcasts are hidden from the default list behind a "Show archived" toggle. Identical to the `events`/`standard-giveaways` archiving requirements, reusing the same dashboard components/patterns.

### 5. Dashboard: new sidebar entry, same list-detail screen pattern
Add a `'broadcasts' => ['label' => 'Broadcasts', 'route' => 'guilds.broadcasts.index']` entry (plus a matching heroicon path and `routeNameToActiveKey` mapping for the index/create/show routes) to `resources/views/components/dashboard-sidebar.blade.php`'s existing `$links`/`$icons` maps - the same three arrays every prior sidebar addition (Events, Standard giveaways) has extended. No new layout/shell work: per `AGENTS.md`, any full-page Livewire component gets the dashboard shell automatically, so the Broadcasts index/create/edit/show screens are ordinary guild-scoped Livewire components using the existing `dashboard-list-detail-layout` master-detail pattern (list of broadcast tiles, detail panel showing the selected series' config and its generated occurrence history), exactly as Events and standard giveaways already do.

## Risks / Trade-offs

- **A typo'd placeholder posts literal `{{...}}` text to Discord** (Decision 1) - accepted trade-off, deliberately chosen over erroring/blocking a scheduled post or silently dropping the token; the Create/Edit form should show the supported placeholder list next to the template field to minimize typos, but that's a UI affordance, not a validation rule.
- **No thread mode** (`proposal.md` Non-goals) - if staff later want per-occurrence discussion threads for a broadcast, that's a straightforward follow-up mirroring Events'/standard giveaways' `posting_mode` field; deliberately deferred since nothing in the request calls for it.

## Migration Plan

Additive only - two new tables (`broadcasts`, `broadcast_occurrences`), no changes to existing event, giveaway, or standard-giveaway tables. `discord_outbound_actions` gains one more `type` enum value (`post_broadcast_message`) plus a nullable `broadcast_occurrence_id` column, exactly as it gained `event_occurrence_id` and `standard_giveaway_occurrence_id` before it. Deploy order: migrate, deploy Laravel with the two new scheduled commands registered, deploy the updated bot process (new outbound action type), then start creating broadcasts.
