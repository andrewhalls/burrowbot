## 1. Project scaffolding

- [x] 1.1 Create the Laravel application (PHP 8.3+), configure `.env.example` for MySQL, queue (database or Redis driver), and session/cache
- [x] 1.2 Install and configure Pest (`pest`, `pest-plugin-laravel`) as the test runner, remove default PHPUnit-only scaffolding
- [x] 1.3 Install Tailwind CSS and the base Blade layout used by the dashboard
- [x] 1.4 Configure a `discord-outbound` queue connection/worker process for outbound Discord actions (design.md Decision 1)
- [x] 1.5 Add `BOT_SERVICE_TOKEN` to config/env and a `bot.service_token` config value
- [x] 1.6 Scaffold the separate Node.js bot project directory (`bot/`) with discord.js, its own `package.json`, `.env.example` (`DISCORD_BOT_TOKEN`, `LARAVEL_BASE_URL`, `BOT_SERVICE_TOKEN`)

## 2. Database schema & models

- [x] 2.1 Migration + model + factory for `guilds` (design.md §2)
- [x] 2.2 Migration + model + factory for `users` (extend default Laravel users table with `discord_user_id`, `avatar_url`)
- [x] 2.3 Migration + model + factory for `guild_admins`
- [x] 2.4 Migration + model + factory for `discord_members`
- [x] 2.5 Migration + model + factory for `collection_themes`
- [x] 2.6 Migration + model + factory for `collection_theme_items`
- [x] 2.7 Migration + model + factory for `giveaways` (status enum: draft/active/closed)
- [x] 2.8 Migration + model + factory for `giveaway_entries`, including the `unique(giveaway_id, discord_member_id)` constraint
- [x] 2.9 Define Eloquent relationships (Guild hasMany CollectionThemes/Giveaways/DiscordMembers; CollectionTheme hasMany CollectionThemeItems; Giveaway belongsTo CollectionTheme, hasMany Entries; Entry belongsTo DiscordMember/CollectionThemeItem)
- [x] 2.10 Pest unit tests for model relationships and the unique-entry constraint

## 3. Internal bot API & authentication

- [x] 3.1 Middleware that validates the `Authorization: Bearer <BOT_SERVICE_TOKEN>` header on all `/internal/*` routes, returning 401 otherwise (spec: `discord-bot-gateway` - Authenticated internal API access)
- [x] 3.2 Pest feature tests: missing token, wrong token, correct token, for a representative internal route

## 4. Guild management

- [x] 4.1 `POST /internal/guilds` and `PATCH /internal/guilds/{guild}` endpoints + Form Requests (spec: `guild-management`)
- [x] 4.2 `GuildSyncAction` (create-on-join, mark-inactive-on-leave)
- [x] 4.3 Guild settings update endpoint/Livewire form (default channel)
- [x] 4.4 Pest feature tests for all `guild-management` scenarios

## 5. Auth & per-guild authorization

- [x] 5.1 Install and configure Laravel Socialite with the Discord driver
- [x] 5.2 Login/callback controller: create-or-update `users` row from the Discord profile, handle consent-denied redirect (spec: `auth` - Discord OAuth sign-in)
- [x] 5.3 `GuildAdminPolicy` (or gate) resolving a user's admin status per `guild_id` from `guild_admins`
- [x] 5.4 Middleware/Livewire trait enforcing guild-scoped authorization on every dashboard route (spec: `auth` - Per-guild admin authorization)
- [x] 5.5 Re-sync job/step that revokes a `guild_admins` row when Discord no longer reports the role
- [x] 5.6 Pest feature tests for all `auth` scenarios, including cross-guild denial

## 6. Member directory

- [x] 6.1 `PUT /internal/guilds/{guild}/members/{discord_user_id}` upsert endpoint (spec: `member-directory` - Member record sync)
- [x] 6.2 `SyncDiscordMemberAction` used by both the internal endpoint and the entry flow
- [x] 6.3 Guild-scoped member search query object/Action (spec: `member-directory` - Member search)
- [x] 6.4 Pest feature/unit tests for sync (create + username update) and guild-scoped search

## 7. Collection themes

- [x] 7.1 Livewire component + Form Request for collection theme create (name + >=1 item), validation error on zero items (spec: `collection-themes` - Collection theme creation)
- [x] 7.2 Livewire component for adding/removing collection theme items, blocked while an active giveaway references the theme (spec: `collection-themes` - Collection theme item management)
- [x] 7.3 `CollectionThemePolicy` guild-scoping theme management to that guild's admins
- [x] 7.4 Pest feature tests for all `collection-themes` scenarios, including the "active giveaway blocks edits" case

## 8. Giveaway lifecycle

- [x] 8.1 Livewire component + Form Request for giveaway creation (channel, collection theme, duration in minutes; positive-integer validation) (spec: `giveaway-lifecycle` - Giveaway creation)
- [x] 8.2 `StartGiveawayAction`: sets `starts_at`/`ends_at`, flips `draft` → `active`, enqueues the `PostGiveawayMessage` outbound job
- [x] 8.3 Scheduled command `giveaways:close-expired` (registered in the scheduler to run every minute): flips expired `active` giveaways to `closed`, enqueues `CloseGiveawayMessage` (spec: `giveaway-lifecycle` - Automatic closing at expiry)
- [x] 8.4 Guard clauses/policy preventing channel/collection-theme/duration edits outside `draft` status (spec: `giveaway-lifecycle` - immutability)
- [x] 8.5 Pest feature tests for creation validation, start transition, scheduled close (including zero-entrant case), and edit-immutability

## 9. Giveaway entry & random assignment

- [x] 9.1 `JoinGiveawayAction` implementing the transactional flow in design.md §3 (row lock, expiry check, unique-insert-for-dedupe, assignment, commit)
- [x] 9.2 Pure `AssignRandomItem` function (unwon-items-first, fall back to full list once exhausted) as an isolated, seedable-RNG unit under test
- [x] 9.3 `POST /internal/giveaways/{giveaway}/entries` endpoint wrapping `JoinGiveawayAction`, returning `won` / `already_entered` / `expired` payloads (spec: `discord-bot-gateway` - Relaying join interactions)
- [x] 9.4 Pest unit tests for `AssignRandomItem`: more items than entrants (no repeats), pool exactly exhausted, pool exhausted then one more entrant (repeat allowed)
- [x] 9.5 Pest feature tests for `JoinGiveawayAction`/endpoint: first join, duplicate join, late join after `ends_at`, concurrent joins on a giveaway with 1 remaining item (spec: `giveaway-entry`, all requirements) — concurrency guaranteed structurally by the row lock (design.md §3) plus the DB unique constraint (tested in ModelRelationshipsTest); true multi-process concurrency isn't exercisable in-process under SQLite

## 10. Outbound Discord actions (Laravel side)

- [x] 10.1 `discord_outbound_actions` migration/model (type, payload, giveaway_id, status, attempts)
- [x] 10.2 `PostGiveawayMessage` and `CloseGiveawayMessage` jobs that enqueue an outbound action record instead of calling Discord directly
- [x] 10.3 `GET /internal/outbound-actions` (pending, since-cursor) and `POST /internal/outbound-actions/{id}/ack` / `/fail` endpoints (design.md Decision 1)
- [x] 10.4 `GET /internal/giveaways/active` endpoint for bot-reconnect recovery (spec: `discord-bot-gateway` - Idempotent recovery on reconnect)
- [x] 10.5 Pest feature tests for outbound action creation, ack/fail transitions, and the active-giveaways recovery payload shape

## 11. Discord bot process (Node.js)

- [x] 11.1 Gateway client bootstrap: login, `GUILD_CREATE`/`GUILD_DELETE`/`GUILD_UPDATE` handlers calling `/internal/guilds*` (spec: `discord-bot-gateway` - and `guild-management` sync)
- [x] 11.2 Startup recovery: call `GET /internal/giveaways/active`, build the in-memory `discord_message_id -> giveaway_id` map
- [x] 11.3 Outbound poller: poll `GET /internal/outbound-actions`, execute `post`/`edit` Discord REST calls, call `/ack` or `/fail`
- [x] 11.4 Interaction handler for the "Join Giveaway" button: call `/internal/.../entries`, reply ephemerally with the mapped result text (spec: `discord-bot-gateway` - Posting/Relaying; `giveaway-entry` - Entrant sees their result)
- [x] 11.5 Member upsert call (`PUT /internal/guilds/{guild}/members/{id}`) on every observed interaction
- [x] 11.6 Process-level error handling/logging and auto-reconnect behavior (discord.js reconnects automatically at the gateway level; added `Events.Error`/`unhandledRejection` logging)
- [x] 11.7 Bot-side test suite (Vitest) for the interaction-result-to-reply-text mapping and the outbound-action-to-Discord-call mapping, using a mocked Discord client, plus laravelClient and messageRoutingStore coverage

## 12. Giveaway admin dashboard

- [x] 12.1 Livewire component: entrant list for a giveaway (username, item, entry time, fulfilled status), guild-scoped via policy (spec: `giveaway-admin-dashboard` - Entrant list, Guild-scoped access)
- [x] 12.2 Search-by-member input wired to the list query (spec: `giveaway-admin-dashboard` - Search entrants by member)
- [x] 12.3 Filter controls: by item, by fulfilment status (spec: `giveaway-admin-dashboard` - Filter by prize item or fulfilment status)
- [x] 12.4 "Mark fulfilled" action recording `fulfilled_at`/`fulfilled_by_user_id` (spec: `giveaway-admin-dashboard` - Mark an entry fulfilled)
- [x] 12.5 Pest/Livewire tests for all `giveaway-admin-dashboard` scenarios, including the 403 on cross-guild access

## 13. Internal API contract & docs

- [x] 13.1 Write an OpenAPI document (`openapi.yaml`) describing every `/internal/*` endpoint from tasks 3-10 (request/response schemas, the bearer auth scheme, and the `won`/`already_entered`/`expired` response variants)
- [x] 13.2 Validate the OpenAPI document lints cleanly (e.g. `redocly lint` or equivalent) and matches the implemented routes

## 14. Cross-cutting polish

- [x] 14.1 Database seeders for local/demo data (a guild, a collection theme with items, a sample giveaway) to support manual QA
- [x] 14.2 CI workflow running Pest (Laravel) and the bot's test suite on every push
- [x] 14.3 README covering local setup for both the Laravel app and the bot process, and how they're wired together
