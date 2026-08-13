## 1. Dependencies & schema

- [x] 1.1 Add `simshaun/recurr` to `composer.json` for RRULE parsing/expansion (design.md Decision 1)
- [x] 1.2 Migration + model + factory for `event_role_sets`
- [x] 1.3 Migration + model + factory for `event_roles` (capacity_mode enum, nullable capacity)
- [x] 1.4 Migration + model + factory for `events` (recurrence_rule, recurrence_start_at, recurrence_timezone, status)
- [x] 1.5 Migration + model + factory for `event_occurrences`, including the snapshotted title/description/channel/posting_mode/role_set columns and the `unique(event_id, scheduled_start_at)` constraint
- [x] 1.6 Migration + model + factory for `event_attendances`, including `unique(event_occurrence_id, discord_member_id)`
- [x] 1.7 Migration + model + factory for `event_role_signups`, including `unique(event_occurrence_id, discord_member_id, event_role_id)`
- [x] 1.8 Define Eloquent relationships (Guild hasMany EventRoleSets/Events; EventRoleSet hasMany EventRoles; Event belongsTo EventRoleSet, hasMany EventOccurrences; EventOccurrence hasMany EventAttendances/EventRoleSignups)
- [x] 1.9 Pest tests for model relationships and all three unique constraints

## 2. Event role sets

- [x] 2.1 Livewire component + validation for role set creation (name, allow_multiple_roles, >=1 role with capacity_mode + capacity) (spec: `event-role-sets` - Role set creation)
- [x] 2.2 Validation: capacity required and positive when capacity_mode is `capped` or `waitlisted`; ignored/null when `uncapped`
- [x] 2.3 `EventRoleSet::isEditable()` helper mirroring `CollectionTheme::isEditable()`, but keyed on "referenced by an occurrence not yet past its scheduled_start_at" (spec: `event-role-sets` - Role set item management)
- [x] 2.4 Livewire component for adding/removing/reconfiguring roles, blocked per 2.3
- [x] 2.5 `EventRoleSetPolicy` guild-scoping management to that guild's admins
- [x] 2.6 Pest feature tests for all `event-role-sets` scenarios, including the three capacity-mode scenarios and the edit-lock/unlock scenarios

## 3. Events (the series)

- [x] 3.1 Livewire component + validation for event creation (title, description, channel, role set, posting_mode, recurrence: none or structured recurrence fields serialized to an RRULE string via `BuildRecurrenceRule`) (spec: `events` - Event creation)
- [x] 3.2 `CreateEventAction`: for a one-off event (no recurrence), also creates its single `event_occurrences` row immediately (spec: `events` - Event creation; `event-occurrences` - One-off events)
- [x] 3.3 Event status transitions (active/paused/cancelled) via a Livewire action + `UpdateEventStatusAction` (spec: `events` - Event series status)
- [x] 3.4 `UpdateEventAction` for editing title/description/channel/role_set/posting_mode/recurrence - only affects occurrence generation going forward, never mutates existing `event_occurrences` rows (spec: `events` - Editing only affects future occurrences)
- [x] 3.5 `EventPolicy` guild-scoping management to that guild's admins
- [x] 3.6 Pest feature tests for all `events` scenarios

## 4. Occurrence generation & posting

- [x] 4.1 `ExpandRecurrenceRule` wrapper around `simshaun/recurr` returning occurrence start times for an event within a given window, as an isolated unit under test
- [x] 4.2 Scheduled command `events:generate-occurrences` (registered to run hourly): for every `active` recurring event, expands its rule up to the rolling window and creates missing `event_occurrences` rows, snapshotting the event's current title/description/channel/posting_mode/role_set (design.md Decision 2-3; spec: `event-occurrences` - Occurrence generation, dedup scenario)
- [x] 4.3 Scheduled command `events:post-due-occurrences`: finds `scheduled` occurrences whose post time has arrived and enqueues a `post_event_occurrence_thread` or `post_event_occurrence_message` outbound action per the occurrence's posting_mode (spec: `event-occurrences` - Posting an occurrence)
- [x] 4.4 `DiscordOutboundAction` type constants + payload shape for the two new event-posting action types - reuses the existing ack/fail endpoints; *did* need one migration after all (`giveaway_id` made nullable, `event_occurrence_id` added) since the original column was `NOT NULL`
- [x] 4.5 Pest tests: RRULE expansion (weekly, monthly, interval, end date/count), generation dedup, one-off event occurrence count, posting-mode branch, event-role-set/title/etc. snapshot isolation from later parent-event edits (covered in Group 3's `UpdateEventActionTest`)

## 5. Event signups

- [x] 5.1 `SignUpForEventRoleAction` implementing the locked transaction in design.md Decision 4 (row lock on occurrence, authoritative start-time cutoff, single-vs-multiple-role handling, capacity/waitlist assignment)
- [x] 5.2 `MarkNotAttendingAction` implementing the Not-Attending transaction (clears role rows, triggers waitlist promotion) (spec: `event-signups` - Marking Not Attending clears role signups)
- [x] 5.3 Waitlist promotion helper (oldest-waitlisted-first) shared by both actions (spec: `event-signups` - Waitlist promotion on capacity release)
- [x] 5.4 `POST /internal/event-occurrences/{occurrence}/signups` endpoint wrapping `SignUpForEventRoleAction`/`MarkNotAttendingAction`, returning confirmed/waitlisted/rejected/not_attending payloads (spec: `discord-bot-gateway` - Relaying event-signup interactions)
- [x] 5.5 Pest tests for the waitlist promotion helper in isolation (feature-level, since it touches the DB - no pure "unit" form exists)
- [x] 5.6 Pest feature tests for `SignUpForEventRoleAction`/`MarkNotAttendingAction`/endpoint covering every scenario in `event-signups`: capacity remaining, capped-blocking full, capped-waitlist full, Not-Attending clears roles, role clears Not-Attending, single-role replace, multiple-role add, waitlist promotion on release, change before start allowed, change after start rejected

## 6. Internal API contract & bot process

- [x] 6.1 Extend `openapi.yaml` with the occurrence-posting outbound action payload shapes and the `POST /internal/event-occurrences/{id}/signups` endpoint (request/response schemas, reusing the existing bearer auth scheme) - also extended `ack` to stamp `discord_thread_id`/`discord_message_id` on the occurrence, and made `discord_outbound_actions.giveaway_id` nullable
- [x] 6.2 Bot: extend the outbound-action executor/adapter (`outboundActionExecutor.js`/`discordAdapter.js`) with `postEventOccurrenceThread` and `postEventOccurrenceMessage`, each rendering the role-selection controls (one select menu + Not Attending button per occurrence) from the action payload, via shared `eventOccurrenceMessage.js`
- [x] 6.3 Bot: interaction handler for role-selection and Not-Attending controls on an occurrence post, calling the new internal signups endpoint and relaying the result (spec: `discord-bot-gateway` - Relaying event-signup interactions) - occurrence id is encoded directly in each component's customId rather than an in-memory routing table, so it survives bot restarts without needing a recovery endpoint
- [x] 6.4 Bot: pure mapping function `eventSignupResultReply(result)` (confirmed/waitlisted/rejected/not_attending -> reply text), unit tested like `joinResultReply`
- [x] 6.5 Bot-side Vitest coverage for the new outbound-action-to-Discord-call mappings and the interaction-result-to-reply-text mapping, using a mocked Discord client (also fixed a pre-existing Vitest config issue: parallel file execution was crashing this sandboxed environment with WASM out-of-memory errors - `fileParallelism: false` resolved it)

## 7. Occurrence roster dashboard

- [x] 7.1 Livewire component: roster for a single occurrence, grouped by role (including a waitlisted sub-section per role) and a Not Attending list, guild-scoped via policy (spec: `giveaway-admin-dashboard`-equivalent UX, but no separate spec capability - reuses `EventPolicy`/`GuildPolicy` patterns)
- [x] 7.2 Search-by-member input wired to the roster query
- [x] 7.3 Pest/Livewire tests: roster grouping/search, and 403 on cross-guild access

## 8. Cross-cutting polish

- [x] 8.1 Database seeder: a role set ("Raid Roles": Tank capped 2, Healer capped-waitlist 2, DPS uncapped), a recurring weekly event, and a generated upcoming occurrence, for manual QA
- [x] 8.2 README section covering the recurrence rule format and the two scheduled commands (`events:generate-occurrences`, `events:post-due-occurrences`) needed alongside the existing `giveaways:close-expired`
