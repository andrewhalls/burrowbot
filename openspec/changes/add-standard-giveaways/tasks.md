## 1. Schema & models

- [x] 1.1 Migration + model + factory for `standard_giveaways` (title, description, channel_id, posting_mode, status, winner_count, requires_booster, duration_minutes, recurrence_rule, recurrence_start_at, recurrence_timezone)
- [x] 1.2 Migration + model + factory for `standard_giveaway_prize_items` (pivot to `collection_theme_items`, `unique(standard_giveaway_id, collection_theme_item_id)`)
- [x] 1.3 Migration + model + factory for `standard_giveaway_required_roles` (`discord_role_id` string, `unique(standard_giveaway_id, discord_role_id)`)
- [x] 1.4 Migration + model + factory for `standard_giveaway_occurrences`, including snapshotted scalar columns, JSON `prize_item_ids`/`required_role_ids` columns (design.md Decision 2 & Risks), `scheduled_post_at`/`posted_at`/`ends_at`, and `unique(standard_giveaway_id, scheduled_post_at)`
- [x] 1.5 Migration + model + factory for `standard_giveaway_entries`, including `unique(standard_giveaway_occurrence_id, discord_member_id)`
- [x] 1.6 Migration + model + factory for `standard_giveaway_winners`, including `unique(standard_giveaway_occurrence_id, standard_giveaway_entry_id)` and a FK to the assigned `collection_theme_item_id`
- [x] 1.7 Define Eloquent relationships (Guild hasMany StandardGiveaways; StandardGiveaway hasMany prize items/required roles/occurrences; StandardGiveawayOccurrence hasMany entries/winners)
- [x] 1.8 Pest tests for model relationships and all unique constraints

## 2. Standard giveaway series

- [x] 2.1 Livewire component + validation for creation (title, description, channel, posting_mode, winner_count >= 1, requires_booster, required role IDs, recurrence fields reusing the Events recurrence picker) (spec: `standard-giveaways` - Standard giveaway creation, Winner count, Eligibility restriction configuration)
- [x] 2.2 Prize-item picker: searchable list across all of the guild's `collection_theme_items` (any theme), multi-select, at least one required (spec: `standard-giveaways` - Prize items selected from existing collection theme items)
- [x] 2.3 `CreateStandardGiveawayAction`: for a one-off giveaway (no recurrence), also creates its single `standard_giveaway_occurrences` row (with snapshotted prize items/roles) immediately (spec: `standard-giveaway-occurrences` - One-off giveaways)
- [x] 2.4 Status transitions (active/paused/cancelled) via `UpdateStandardGiveawayStatusAction` (spec: `standard-giveaways` - Standard giveaway series status)
- [x] 2.5 `UpdateStandardGiveawayAction` for editing - only affects occurrence generation going forward, never mutates existing `standard_giveaway_occurrences` rows (spec: `standard-giveaways` - Editing only affects future occurrences)
- [x] 2.6 `StandardGiveawayPolicy` guild-scoping management to that guild's admins
- [x] 2.7 Pest feature tests for all `standard-giveaways` scenarios

## 3. Occurrence generation & posting

- [x] 3.1 Scheduled command `standard-giveaways:generate-occurrences` (hourly), reusing `ExpandRecurrenceRule` exactly as `events:generate-occurrences` does, snapshotting the giveaway's current prize items/required roles into the occurrence's JSON columns at generation time (spec: `standard-giveaway-occurrences` - Occurrence generation, One-off giveaways)
- [x] 3.2 Scheduled command `standard-giveaways:post-due-occurrences` (every minute): posts every `scheduled` occurrence, stamps `posted_at`, computes `ends_at = posted_at + duration_minutes` (design.md Decision 2) (spec: `standard-giveaway-occurrences` - Posting an occurrence)
- [x] 3.3 `DiscordOutboundAction` type constants + payload shape for `post_standard_giveaway_thread`/`post_standard_giveaway_message` (reuses the existing outbound actions table/ack/fail endpoints; added nullable `standard_giveaway_occurrence_id` column, mirroring `event_occurrence_id`)
- [x] 3.4 Pest tests: generation dedup, one-off occurrence count, snapshot isolation from later parent-giveaway edits (covered in Group 2's `UpdateStandardGiveawayActionTest`), posting-mode branch, `ends_at` computed from `posted_at` not `scheduled_post_at`

## 4. Closing & drawing

- [x] 4.1 Pure `DrawRandomWinners` function (draws `min(winner_count, count(pool))` distinct entries at random, injectable randomizer) as an isolated unit under test (design.md Decision 3)
- [x] 4.2 `CloseAndDrawStandardGiveawayOccurrenceAction`: locked transaction per design.md Decision 3 - closes the occurrence, draws winners via `DrawRandomWinners`, assigns prize items via the existing `AssignRandomItem` (accumulating across this occurrence's winners only), records `standard_giveaway_winners`, enqueues the "announce winners" outbound action
- [x] 4.3 Scheduled command `standard-giveaways:close-expired` (every minute, named to match `giveaways:close-expired`): finds `posted` occurrences past `ends_at` and runs 4.2, idempotently (spec: `standard-giveaway-occurrences` - Automatic closing and drawing at end time)
- [x] 4.4 Pest unit tests for `DrawRandomWinners`: enough entrants, fewer entrants than winner count, zero entrants
- [x] 4.5 Pest feature tests for `CloseAndDrawStandardGiveawayOccurrenceAction`/scheduled command covering every scenario in `standard-giveaway-occurrences`'s closing/drawing and fair-assignment requirements, plus idempotency (running the command twice doesn't double-draw)

## 5. Standard giveaway entries

- [x] 5.1 `SubmitStandardGiveawayEntryAction` implementing the locked-transaction entry flow: authoritative `ends_at` cutoff, booster check, required-role check (any-of), one-entry-per-member (spec: `standard-giveaway-entries`, all requirements)
- [x] 5.2 `POST /internal/standard-giveaway-occurrences/{occurrence}/entries` endpoint accepting `discord_user_id`, `discord_username`, `discord_role_ids`, `is_boosting` (design.md Decision 1), wrapping 5.1, returning `entered`/`already_entered`/`rejected`/`closed` payloads
- [x] 5.3 Pest feature tests for `SubmitStandardGiveawayEntryAction`/endpoint: open giveaway entry, booster-only accept/reject, role-restricted accept/reject, combined restriction, duplicate entry, entry after close rejected (including the "status not yet flipped but ends_at passed" race)

## 6. Internal API contract & bot process

- [x] 6.1 Extend `openapi.yaml` with the occurrence-posting outbound action payload shapes, the `POST /internal/standard-giveaway-occurrences/{id}/entries` endpoint, and the `announce_standard_giveaway_winners` outbound action shape
- [x] 6.2 Bot: extend the outbound-action executor/adapter with `postStandardGiveawayThread`/`postStandardGiveawayMessage` (embed naming the prize item(s), any restriction, and the end time, plus an "Enter" button) and `announceStandardGiveawayWinners` (edits/replies in the occurrence's channel/thread naming winners and their item)
- [x] 6.3 Bot: interaction handler for the "Enter" button, reading `interaction.member.roles` and `interaction.member.premium_since` and forwarding them to the internal entries endpoint (spec: `discord-bot-gateway` - Relaying standard giveaway entry interactions with eligibility data); occurrence id encoded in the button's customId, same pattern as event occurrences (no routing-table dependency)
- [x] 6.4 Bot: pure mapping function `standardGiveawayEntryResultReply(result)` (entered/already_entered/rejected/closed -> reply text), unit tested like `joinResultReply`/`eventSignupResultReply`
- [x] 6.5 Bot-side Vitest coverage for the new outbound-action-to-Discord-call mappings and the interaction-result-to-reply-text mapping, using a mocked Discord client

## 7. Occurrence dashboard

- [x] 7.1 Livewire component: entrant list and drawn-winners list for a single occurrence, guild-scoped via policy, searchable by member (spec: reuses the `giveaway-admin-dashboard`/occurrence-roster UX pattern; no separate spec capability)
- [x] 7.2 Pest/Livewire tests: entrant/winner display, search, 403 on cross-guild access

## 8. Cross-cutting polish

- [x] 8.1 Database seeder: a booster-only "Nitro Friday" standard giveaway with one pre-set prize item (reusing the seeded "Retro Arcade" theme's items) and a weekly recurrence, for manual QA
- [x] 8.2 README section covering the three new scheduled commands (`standard-giveaways:generate-occurrences`, `standard-giveaways:post-due-occurrences`, `standard-giveaways:close-expired`) alongside the existing giveaway/event ones
