## Context

See `proposal.md` - Why/What Changes for motivation and scope. This builds on the existing giveaway platform's architecture (`openspec/changes/archive/2026-08-12-add-discord-giveaway-platform/design.md` and the current `openspec/specs/discord-bot-gateway`): Laravel owns all state and business rules; the Node.js bot process only relays Discord events and executes the Discord-facing actions Laravel asks for via the internal `/internal/*` API and the outbound-actions poll loop. Events reuse that same architecture rather than inventing a new one.

## Goals / Non-Goals

**Goals:**
- Fix the data model for role sets, events, occurrences, and signups so capacity/waitlist/multi-role behavior (specs/event-signups) is unambiguous and race-safe under concurrent Discord interactions.
- Fix the recurrence rule format and how/when occurrences are generated and posted.
- Fix the waitlist promotion algorithm.
- Reuse the existing bot↔Laravel outbound-action/poll contract rather than adding a second integration pattern.

**Non-Goals:**
- A general-purpose calendar/scheduling engine - only what's needed to expand one event's recurrence rule into occurrence start times.
- Real-time push of roster updates to Discord beyond what a button/select-menu interaction reply already provides (no live-editing the posted embed as others sign up, beyond what's needed to show current counts on next render).

## Decisions

### 1. Recurrence rule storage and expansion
**Decision:** Store the recurrence rule as an RFC 5545 `RRULE` string (e.g. `FREQ=WEEKLY;BYDAY=WE;INTERVAL=1`) on `events.recurrence_rule` (nullable - null means one-off), plus `events.recurrence_start_at` (the anchor date/time and timezone the rule is evaluated from) and optionally `recurrence_ends_at` / `recurrence_count` (mirroring RRULE's `UNTIL`/`COUNT`). Expand it using the `simshaun/recurr` Composer package, which implements RFC 5545 and is the de facto standard for this in PHP.

**Alternatives considered:**
- *Custom structured recurrence (frequency enum + interval + days-of-week columns)* - rejected per explicit product decision for "full custom recurrence rule"; RRULE is the standard, well-tested representation for that and avoids re-inventing edge cases (monthly-by-weekday, leap years, DST).
- *Hand-rolled RRULE parser* - rejected; RRULE has enough edge cases (BYSETPOS, DST transitions) that a maintained library is the safer choice.

The dashboard's event form doesn't need to expose raw RRULE text to admins - it presents a structured picker (frequency, interval, days, end condition) and serializes it to an RRULE string server-side; that UI mapping is an implementation detail, not a spec-level concern.

### 2. Occurrence generation: a scheduled job, not on-demand
**Decision:** A scheduled command (`events:generate-occurrences`, run e.g. hourly) finds every `active` recurring event, expands its RRULE up to a rolling window (e.g. next 30 days), and creates any `event_occurrences` rows that don't already exist for a computed start time (dedup key: `event_id` + `scheduled_start_at`). A separate scheduled command (`events:post-due-occurrences`) finds generated-but-not-yet-posted occurrences whose post time has arrived (post lead time is a per-event setting, default: immediately on generation) and enqueues the "post occurrence" outbound action - the same `DiscordOutboundAction` pattern giveaways use (design.md Decision 1 of the giveaway platform), with new types `post_event_occurrence_thread` / `post_event_occurrence_message`.

**Alternatives considered:**
- *Generate the next occurrence lazily when the previous one completes* - rejected: makes "how far ahead can staff see upcoming occurrences" unpredictable and complicates pause/cancel/edit semantics (specs/events - "Editing only affects future occurrences" needs a clear generation boundary).

### 3. Data model

```
event_role_sets
  id (pk), guild_id (fk), name, allow_multiple_roles (bool), timestamps

event_roles
  id (pk), event_role_set_id (fk), name, sort_order,
  capacity_mode (enum: uncapped | capped | waitlisted), capacity (nullable int,
  required when capacity_mode != uncapped), timestamps

events
  id (pk), guild_id (fk), event_role_set_id (fk), title, description,
  channel_id, posting_mode (enum: thread | message),
  status (enum: active | paused | cancelled),
  recurrence_rule (nullable text, RRULE), recurrence_start_at (nullable timestamp),
  recurrence_timezone (string, IANA tz name), timestamps

event_occurrences
  id (pk), event_id (fk),
  -- snapshotted from the event at generation time, per Decision "editing only
  -- affects future occurrences":
  title, description, channel_id, posting_mode, event_role_set_id (fk),
  scheduled_start_at, status (enum: scheduled | posted | completed | cancelled),
  discord_thread_id (nullable), discord_message_id (nullable), timestamps
  unique (event_id, scheduled_start_at)

event_attendances
  id (pk), event_occurrence_id (fk), discord_member_id (fk),
  status (enum: attending | not_attending), timestamps
  unique (event_occurrence_id, discord_member_id)

event_role_signups
  id (pk), event_occurrence_id (fk), discord_member_id (fk), event_role_id (fk),
  is_waitlisted (bool), created_at
  unique (event_occurrence_id, discord_member_id, event_role_id)
```

`event_occurrences` snapshots title/description/channel/posting_mode/role_set from its parent `events` row at generation time rather than joining live, which is what makes specs/events' "editing an event only affects occurrences generated after the edit" true by construction - no extra bookkeeping needed.

`event_attendances` and `event_role_signups` are two tables rather than one because "Not Attending" and "holds role X" are different shapes: an attendance row always exists once a member has responded at all (attending or not), while role rows are zero-or-more (zero while Not Attending or unresponsive, one-or-more while attending, depending on `allow_multiple_roles`). This directly implements specs/event-signups: marking Not Attending sets `event_attendances.status = not_attending` and deletes all of that member's `event_role_signups` rows for the occurrence in the same transaction; selecting a role upserts `event_attendances.status = attending` and adds a role row (deleting the member's other role row first when `allow_multiple_roles` is false).

### 4. Signup transaction & waitlist promotion

Mirrors the giveaway platform's `JoinGiveawayAction` pattern: a `SignUpForEventRoleAction` (and `MarkNotAttendingAction`) runs inside a DB transaction that locks the occurrence row (`SELECT ... FOR UPDATE`) to serialize concurrent signups on the same occurrence - the same concurrency-safety mechanism already established, not a new one.

Within that lock:
1. Reject if `now() >= occurrence.scheduled_start_at` (authoritative cutoff, independent of `status`, mirroring the giveaway platform's expiry check).
2. If switching to Not Attending: delete all the member's role rows for this occurrence (capturing which roles/waitlist-state they held), upsert attendance to `not_attending`, then run waitlist promotion (step 4) for each role that lost a *confirmed* member.
3. If selecting a role: upsert attendance to `attending`; if `allow_multiple_roles` is false, delete the member's existing role row first (if different from the new one) and run waitlist promotion for it; insert the new role row as confirmed if capacity remains (uncapped, or `count(confirmed) < capacity`), else as waitlisted (capped-with-waitlist) or reject (capped-blocking, checked before insert).
4. Waitlist promotion for a role: if a confirmed row was just freed, find the oldest `is_waitlisted = true` row for that role (`ORDER BY created_at ASC LIMIT 1`) and flip it to confirmed (`is_waitlisted = false`).

## Risks / Trade-offs

- **RRULE expressiveness vs. UI complexity** - supporting the full RRULE grammar in a dashboard form is more UI work than a fixed "weekly on day X" picker. Mitigation: the form only needs to expose the common cases (daily/weekly/monthly, interval, days, end date/count) and can serialize to RRULE under the hood; full RRULE syntax stays an internal representation, not something admins type directly.
- **Occurrence snapshotting means role set edits don't retroactively fix a typo on already-generated occurrences** - accepted trade-off; specs/events explicitly requires this (editing only affects future occurrences), so it's already fair.
- **Hourly generation window could theoretically miss a fast-approaching occurrence if the job is delayed** - mitigation: the posting job runs independently and only depends on `scheduled_start_at` already existing in the DB, and generation runs well ahead of the window (30 days), so a single delayed run has no visible effect.

## Migration Plan

Additive only - six new tables, no changes to existing giveaway tables. Deploy order: run migrations, add `simshaun/recurr` to `composer.json`, deploy Laravel with the new scheduled commands registered, deploy the updated bot process (new outbound action types + new interaction handler), then start creating events.

## Open Questions

- Default post lead time for a newly generated occurrence (Decision 2 assumes "immediately on generation" as the default) - deferred: it's a per-event, editable setting, so shipping with immediate-posting as the default doesn't lock in the wrong behavior; can be revisited without touching the specs, approach, or task breakdown.
