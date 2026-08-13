## Context

See `proposal.md` - Why/What Changes for motivation and scope. This is a greenfield build: there is no existing Laravel app, database, or bot process to integrate with, so this design also fixes the initial architecture, not just this feature's slice of it.

Two runtime processes exist:
1. **Laravel app** (PHP 8.3+, MySQL 8) - single source of truth for all domain state (guilds, collection themes, giveaways, entries, members, auth) and all business rules (who can join, what they win, when a giveaway closes). Ships an HTTP admin dashboard (Livewire) and an internal HTTP API used only by the bot.
2. **Bot process** (Node.js + discord.js) - the only process holding a Discord bot token / gateway connection. It is a thin relay: it turns Discord gateway events into calls against Laravel's internal API, and turns Laravel's responses into Discord REST calls (post message, edit message, reply to interaction). It holds no business logic and no database connection of its own.

## Goals / Non-Goals

**Goals:**
- Fix the process boundary and communication contract between Laravel and the bot so both sides can be built and tested independently.
- Fix the data model for guilds, collection themes/items, giveaways, and entries so every later capability (auth, dashboard, entry) builds on the same shapes.
- Specify the random item assignment algorithm precisely enough to unit test.
- Specify how expiry is enforced authoritatively, closing the "late click still wins" race condition.

**Non-Goals:**
- Deployment topology (containers vs. VM vs. specific host) - left to the operator; this design only assumes "two long-running processes that can reach each other over HTTP and share no other state."
- Discord slash-command UX for creating giveaways from within Discord - v1 creation happens on the web dashboard only (see proposal.md - Non-goals).
- Horizontal scaling of the bot process - v1 assumes a single bot process per Discord application (sharding is out of scope until guild count requires it).

## Decisions

### 1. Bot talks to Laravel over an internal REST API; Laravel never calls Discord directly
**Decision:** All Discord I/O (post, edit, reply) is performed by the bot process, triggered either by (a) a Discord gateway event it received, or (b) Laravel enqueuing an "outbound Discord action" the bot polls/receives and executes.

Concretely:
- **Bot → Laravel** (synchronous, request/response): the bot calls this whenever it needs Laravel to make a decision.
  - `POST /internal/giveaways/{giveaway}/entries` - body: `{ discord_user_id, discord_username }`. Laravel validates the giveaway is active and not expired, applies the assignment algorithm (Decision 3), persists the entry, and returns `{ status: "won", item }`, `{ status: "already_entered", item }`, or `{ status: "expired" }`. The bot maps this response directly to the ephemeral Discord reply text - it does not re-implement any of that logic.
  - `POST /internal/guilds` (guild join) / `PATCH /internal/guilds/{guild}` (guild leave/rename) - keeps `guild-management` in sync with gateway `GUILD_CREATE`/`GUILD_DELETE`/`GUILD_UPDATE` events.
  - `PUT /internal/guilds/{guild}/members/{discord_user_id}` - upserts a member record (`member-directory`), called opportunistically on any interaction from that member.
  - `GET /internal/giveaways/active` - called once on bot startup/reconnect so the bot can rebuild its in-memory map of `{ discord_message_id -> giveaway_id }` for interaction routing, without ever re-posting.
- **Laravel → Bot** (asynchronous, fire-and-forget from Laravel's perspective): Laravel never opens an outbound connection to the bot. Instead, Laravel enqueues a job (`PostGiveawayMessage`, `CloseGiveawayMessage`) onto a `discord-outbound` Laravel queue; a small worker endpoint the bot polls, `GET /internal/outbound-actions?since=...` (long-poll or short-interval poll), returns pending actions; the bot executes the Discord call and then calls `POST /internal/outbound-actions/{id}/ack` (or `/fail` with a reason) so Laravel can retry failed sends via the normal queue retry/backoff.
  - This keeps the bot from needing an inbound port reachable from Laravel (simpler deployment, works even if the bot runs behind NAT) at the cost of poll latency, which is acceptable since "post the message" and "close the giveaway" are not sub-second-latency-critical actions.

**Auth:** every `/internal/*` request carries a bot-only bearer service token (`BOT_SERVICE_TOKEN`, a single long-lived secret shared via environment/secret manager, not tied to a specific human user) in `Authorization: Bearer <token>`. A dedicated middleware rejects anything else with 401 before it reaches a controller. This is separate from the Socialite-based human dashboard auth in `auth`.

**Alternatives considered:**
- *PHP-native gateway bot (e.g. discord-php) in the same codebase as Laravel* - rejected: PHP gateway libraries are less mature/maintained than discord.js, and a persistent gateway connection inside a PHP-FPM/web-request process model is awkward; would need its own long-running worker anyway, so the separation gains nothing.
- *Bot writes directly to MySQL* - rejected: duplicates business logic (expiry checks, assignment) in two languages/processes and risks them drifting; violates "one source of truth."
- *Webhook-based HTTP Interactions (no persistent gateway)* - considered and explicitly rejected per the user's stated preference for a persistent gateway bot process; also would require a public HTTPS endpoint for Discord to reach, which the polling-based design above avoids needing in the other direction.

### 2. Data model

```
guilds
  id (pk), discord_guild_id (unique), name, default_channel_id (nullable), is_active, timestamps

users                       -- dashboard staff, Socialite-authenticated
  id (pk), discord_user_id (unique), username, avatar_url, timestamps

guild_admins                -- per-guild authorization (auth + guild-management)
  id (pk), guild_id (fk), user_id (fk), role, timestamps
  unique (guild_id, user_id)

discord_members             -- member-directory, per guild
  id (pk), guild_id (fk), discord_user_id, username, avatar_url, timestamps
  unique (guild_id, discord_user_id)

collection_themes           -- collection-themes
  id (pk), guild_id (fk), name, timestamps

collection_theme_items      -- the item list per collection theme
  id (pk), collection_theme_id (fk), name, sort_order, timestamps

giveaways                   -- giveaway-lifecycle
  id (pk), guild_id (fk), collection_theme_id (fk), channel_id (discord channel id),
  duration_minutes, status (enum: draft|active|closed), discord_message_id (nullable),
  starts_at (nullable), ends_at (nullable), timestamps

giveaway_entries            -- giveaway-entry
  id (pk), giveaway_id (fk), discord_member_id (fk -> discord_members),
  collection_theme_item_id (fk -> collection_theme_items), fulfilled_at (nullable),
  fulfilled_by_user_id (nullable fk -> users), created_at
  unique (giveaway_id, discord_member_id)   -- enforces one entry per member
```

`unique (giveaway_id, discord_member_id)` is what makes "one entry per member" (giveaway-entry) race-safe under concurrent clicks: the second insert fails at the database, and the application catches that and returns the existing entry rather than relying on an application-level check-then-insert.

### 3. Random item assignment algorithm

Implemented as a single DB transaction inside the join Action, so it is race-safe under concurrent joins on the same giveaway:

1. Begin transaction; lock the giveaway row (`SELECT ... FOR UPDATE`) to serialize concurrent joins on the same giveaway.
2. If `now() >= giveaway.ends_at` or `status != active` → rollback, return `expired`.
3. Attempt to insert the entry row with no `collection_theme_item_id` yet, relying on the unique constraint to short-circuit duplicates → if it violates the unique constraint, rollback the insert, return `already_entered` with the existing entry's item.
4. Compute `unwon_item_ids` = collection theme's item ids minus item ids already present in `giveaway_entries` for this giveaway.
   - If `unwon_item_ids` is non-empty: pick one uniformly at random from it (draw without replacement across the giveaway).
   - Else (pool exhausted - every item has been won at least once): pick one uniformly at random from *all* the collection theme's item ids (draw with replacement).
5. Update the entry row with the chosen `collection_theme_item_id`, commit, return `won` + the item.

This directly implements the `giveaway-entry` requirement "Random item assignment without early repeats": every entrant gets a distinct item until the pool is exhausted, after which repeats are allowed rather than the giveaway breaking or rejecting entrants. Fairness (uniform randomness) is testable in isolation as a pure function `assignItem(unwonItemIds, allItemIds): itemId` given a seeded RNG.

### 4. Authoritative expiry enforcement

Two independent mechanisms, so a late click can never win even if the scheduled close is delayed:
- **Request-time check (authoritative):** step 2 of the assignment algorithm above compares `now()` to `giveaway.ends_at` on *every* join request, regardless of whether the giveaway's `status` has been flipped to `closed` yet. This is the actual guarantee - it does not depend on the scheduler having run.
- **Scheduled close (housekeeping):** a Laravel scheduled job runs every minute (`giveaways:close-expired`), finds `active` giveaways whose `ends_at` has passed, flips them to `closed`, and enqueues the `CloseGiveawayMessage` outbound action so the Discord message visibly stops accepting entries. This is what makes the button *look* disabled promptly; it is not what makes late entries *actually* rejected.

Because Discord button interactions always reach the bot and are always forwarded to Laravel's join endpoint (the bot does not pre-filter by "does the button look enabled"), a click that Discord delivers after `ends_at` - even if the message hadn't been edited yet - is rejected by the request-time check.

## Risks / Trade-offs

- **Polling latency for outbound actions** (Decision 1) → a giveaway's Discord message may take up to the poll interval (target: 2-5s) to appear after "start" and to visibly close after `ends_at`. Mitigation: the join endpoint's authoritative expiry check means this latency never affects fairness, only UX polish; keep the poll interval short and consider upgrading to a push mechanism (Redis pub/sub or a WebSocket) later if it's felt.
- **Single bot process is a single point of failure** for posting/relaying → giveaways already `active` in Laravel keep accepting/rejecting joins correctly via the request-time check once the bot reconnects, but no new giveaways can be posted and no interactions relay while it's down. Mitigation: process supervisor with auto-restart; `GET /internal/giveaways/active` on reconnect (Decision 1) ensures no duplicate posts.
- **Uniform-random "with replacement after exhaustion"** means a very popular giveaway with a small collection theme can hand out the same item to many people. This is a deliberate, documented product trade-off (see `giveaway-entry` spec), not a bug.

## Migration Plan

Greenfield build, so there is no data migration. Deployment order for a new environment:
1. Provision MySQL, run Laravel migrations.
2. Deploy Laravel app with `BOT_SERVICE_TOKEN` set.
3. Deploy bot process with the same `BOT_SERVICE_TOKEN`, Discord bot token, and Laravel base URL.
4. Invite the bot to a guild → confirms the `guild-management` sync path end-to-end before any giveaway is created.

## Open Questions

- Exact poll vs. push mechanism for the outbound-actions queue (Decision 1) beyond v1 - deferred; does not change the API contract shape (`GET .../outbound-actions`), only its transport, so it can be revisited without touching specs or tasks.
