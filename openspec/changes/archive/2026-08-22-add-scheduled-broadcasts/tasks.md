## 1. Schema & models

- [x] 1.1 Migration + model + factory for `broadcasts` (`guild_id`, `title`, `message_template`, `channel_id`, `status`, `archived_at`, `recurrence_rule`, `recurrence_start_at`, `recurrence_timezone`, `created_by`)
- [x] 1.2 Migration + model + factory for `broadcast_occurrences` (`broadcast_id`, snapshotted `message_template`/`channel_id`, `scheduled_post_at`, `posted_at`, `discord_message_id`, `status`, `unique(broadcast_id, scheduled_post_at)`)
- [x] 1.3 Define Eloquent relationships (Guild hasMany Broadcasts; Broadcast hasMany BroadcastOccurrences)
- [x] 1.4 Pest tests for model relationships and the unique constraint on `broadcast_occurrences`

## 2. Broadcast series

- [x] 2.1 Livewire component + validation for creation (title, message template, channel via the existing `discord-channels` picker, recurrence fields reusing the Events recurrence picker) (spec: `broadcasts` - Broadcast creation)
- [x] 2.2 `CreateBroadcastAction`: for a one-off broadcast (no recurrence), also creates its single `broadcast_occurrences` row immediately (spec: `broadcast-occurrences` - One-off broadcasts generate exactly one occurrence)
- [x] 2.3 Status transitions (active/paused/cancelled) via `UpdateBroadcastStatusAction` (spec: `broadcasts` - Broadcast series status)
- [x] 2.4 `UpdateBroadcastAction` for editing - only affects occurrence generation going forward, never mutates existing `broadcast_occurrences` rows (spec: `broadcasts` - Editing only affects future occurrences)
- [x] 2.5 Archive/unarchive via the existing archiving pattern (`ArchiveBroadcastAction`/`UnarchiveBroadcastAction`), "Show archived" list toggle (spec: `broadcasts` - Archiving, Archived broadcasts hidden by default)
- [x] 2.6 `DeleteBroadcastAction`, rejecting deletion once any occurrence has posted (spec: `broadcasts` - Deleting a broadcast series)
- [x] 2.7 `BroadcastPolicy` guild-scoping management to that guild's admins
- [x] 2.8 Pest feature tests for all `broadcasts` scenarios

## 3. Message template placeholders

- [x] 3.1 Pure `RenderBroadcastMessage` function/action: resolves `{{channel}}`, `{{guild_name}}`, `{{date}}`, `{{time}}`, `{{next_occurrence_date}}` from a small resolved-values input, leaves unrecognized `{{...}}` tokens as literal text, as an isolated unit under test (design.md Decision 1)
- [x] 3.2 Pest unit tests for `RenderBroadcastMessage`: all placeholders present, no placeholders, unrecognized placeholder left literal, empty `{{next_occurrence_date}}` (spec: `broadcast-occurrences` - Message template placeholders, all scenarios)

## 4. Occurrence generation & posting

- [x] 4.1 Scheduled command `broadcasts:generate-occurrences` (hourly), reusing `ExpandRecurrenceRule` exactly as `events:generate-occurrences` does, snapshotting the broadcast's current `message_template`/`channel_id` into the occurrence at generation time (spec: `broadcast-occurrences` - Occurrence generation, One-off broadcasts)
- [x] 4.2 Scheduled command `broadcasts:post-due-occurrences` (every minute): for each due `scheduled` occurrence, computes `{{next_occurrence_date}}` via `ExpandRecurrenceRule` peeked one step past the occurrence's `scheduled_post_at` (design.md Decision 3), resolves the template via `RenderBroadcastMessage`, and enqueues the outbound action that posts it, stamping `posted_at` at enqueue time and `discord_message_id` on ack (matches the established `events:post-due-occurrences`/`standard-giveaways:post-due-occurrences` pattern, not a wait-for-ack design) (spec: `broadcast-occurrences` - Posting an occurrence)
- [x] 4.3 `DiscordOutboundAction` type constant + payload shape for `post_broadcast_message` (reuses the existing outbound actions table/ack/fail endpoints; adds nullable `broadcast_occurrence_id` column, mirroring `event_occurrence_id`/`standard_giveaway_occurrence_id`)
- [x] 4.4 Pest tests: generation dedup, one-off occurrence count, snapshot isolation from later parent-broadcast edits, `{{date}}`/`{{time}}` computed from the actual post moment not `scheduled_post_at`, idempotency (running `post-due-occurrences` twice doesn't double-post)

## 5. Internal API contract & bot process

- [x] 5.1 Extend `openapi.yaml` with the broadcast-occurrence-posting outbound action payload shape
- [x] 5.2 Bot: extend the outbound-action executor/adapter with `postBroadcastMessage` (posts the resolved text as-is, no bot-side transformation) (spec: `discord-bot-gateway` - Posting a broadcast message, Bot performs no placeholder resolution)
- [x] 5.3 Bot-side Vitest coverage for the new outbound-action-to-Discord-call mapping, using a mocked Discord client

## 6. Dashboard

- [x] 6.1 Add a "Broadcasts" entry to `resources/views/components/dashboard-sidebar.blade.php`'s `$links`, `$icons`, and `$routeNameToActiveKey` maps, alongside Events and Giveaways (spec: `broadcasts` - Broadcasts dashboard navigation entry)
- [x] 6.2 Guild-scoped route (`guilds.broadcasts.index`) and Livewire components for the broadcast list-detail screen, reusing `dashboard-list-detail-layout` (tile list + detail panel showing series config and occurrence history) exactly as Events and standard giveaways do - create/edit render inline in the detail panel via `showCreateForm`/`editing`, matching the established Events/standard-giveaways pattern rather than separate `.create`/`.show` routes
- [x] 6.3 Create/Edit broadcast form: title, message template textarea (with the supported placeholder list shown alongside it), channel picker, recurrence picker
- [x] 6.4 Pest/Livewire tests: list rendering, create/edit/archive/delete flows, occurrence history display, 403 on cross-guild access

## 7. Cross-cutting polish

- [x] 7.1 Database seeder: a weekly "Raid Reset Reminder" broadcast in a seeded channel, for manual QA
- [x] 7.2 README section covering the two new scheduled commands (`broadcasts:generate-occurrences`, `broadcasts:post-due-occurrences`) alongside the existing event/giveaway/standard-giveaway ones
