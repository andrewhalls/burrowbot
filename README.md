# Burrow

A Discord event management and bot platform. Two flagship features:

- **Pop-up giveaways**: a message posted in a Discord channel with a "Join
  Giveaway" button; each entrant is instantly assigned a random item from a
  themed collection; the giveaway auto-closes after an admin-set duration;
  staff get a searchable/filterable dashboard to hand items out.
- **Events with role signups**: staff define a reusable role set (e.g. "Raid
  Roles": Tank/Healer/DPS, each with its own capacity/waitlist behavior),
  attach it to a one-off or recurring event, and Discord itself becomes the
  signup sheet — each occurrence posts as a new thread or message with a
  role-select menu and a Not Attending button, and staff get a per-occurrence
  roster dashboard.

The full spec (requirements, architecture, task breakdown) lives in
[`openspec/`](openspec/) — see [`AGENTS.md`](AGENTS.md) for how to navigate
it. The internal bot↔Laravel API contract is documented in
[`openapi.yaml`](openapi.yaml).

## Architecture

Two processes:

1. **This Laravel app** (`/`) — single source of truth for all data and
   business rules. Serves the staff dashboard (Livewire) and an internal
   `/internal/*` API used only by the bot process.
2. **The bot** (`/bot`) — a Node.js/discord.js process. The only thing that
   talks to Discord. Holds no business logic: it relays Discord gateway
   events to Laravel's internal API and executes the Discord-facing actions
   Laravel asks it to (post/edit a giveaway message, reply to an entrant).

See [`openspec/specs/`](openspec/specs/) for current behavior and
[`openspec/changes/`](openspec/changes/) for in-progress/archived design
rationale, including
[`add-discord-giveaway-platform`](openspec/changes/archive/2026-08-12-add-discord-giveaway-platform/design.md)
and [`add-events-with-role-signups`](openspec/changes/add-events-with-role-signups/design.md).

## Prerequisites

- PHP 8.3+, Composer
- Node.js 20+ (both for the Laravel app's asset build and for the bot)
- A Discord application (for real Discord OAuth login + a real bot token) —
  not required to run the test suite or explore the dashboard with seeded
  data, only for talking to real Discord servers

## Laravel app setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # or point DB_* at MySQL in .env
php artisan migrate --seed
npm install
npm run dev                      # Vite dev server, in a separate terminal
php artisan serve
```

The seeder creates a demo guild, an admin user, a "Retro Arcade" collection
theme with items, a draft giveaway, a "Raid Roles" event role set
(Tank: capped at 2, Healer: capped at 2 with a waitlist, DPS: uncapped), and
a weekly recurring "Raid Night" event, so the dashboard has something to
show immediately. Demo data uses placeholder Discord IDs — real dashboard
login still requires a working Discord OAuth app (below).

Run the test suite:

```bash
./vendor/bin/pest
```

### Discord OAuth (dashboard login)

Create a Discord application at the
[Discord Developer Portal](https://discord.com/developers/applications),
add an OAuth2 redirect matching `APP_URL/auth/discord/callback`, and set in
`.env`:

```
DISCORD_CLIENT_ID=...
DISCORD_CLIENT_SECRET=...
```

### Event recurrence rules & scheduled commands

An event's recurrence is stored as an [RFC 5545 `RRULE`](https://icalendar.org/iCalendar-RFC-5545/3-8-5-3-recurrence-rule.html)
string (e.g. `FREQ=WEEKLY;BYDAY=WE`) — the dashboard's structured recurrence
picker (frequency, interval, days of week, end condition) serializes to this
format for you; you never type an RRULE by hand. `null` means a one-off
event.

Two scheduled commands, registered in `routes/console.php`, drive the
lifecycle (alongside the giveaway platform's `giveaways:close-expired`):

- `events:generate-occurrences` (hourly) — expands each active recurring
  event's rule up to a 30-day rolling window and creates any missing
  `event_occurrences` rows.
- `events:post-due-occurrences` (every minute) — enqueues the outbound
  action that posts each `scheduled` occurrence to Discord (as a new thread
  or a new plain message, per the event's posting mode).

Run them manually while developing:

```bash
php artisan events:generate-occurrences
php artisan events:post-due-occurrences
```

### Bot service token

`.env`'s `BOT_SERVICE_TOKEN` is the shared secret the bot process uses to
authenticate to `/internal/*`. Generate one and set the **same value** in
`bot/.env`:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

## Bot process setup

```bash
cd bot
npm install
cp .env.example .env
# Fill in DISCORD_BOT_TOKEN (from the Discord Developer Portal),
# LARAVEL_BASE_URL (e.g. http://localhost:8000), and BOT_SERVICE_TOKEN
# (must match the Laravel app's .env).
npm start
```

Run the bot's test suite:

```bash
npm test
```

The bot has no database of its own — every piece of state it needs comes
from Laravel's `/internal/*` API (documented in [`openapi.yaml`](openapi.yaml)).

## Running both together locally

1. Start the Laravel app (`php artisan serve`) and its queue worker
   (`php artisan queue:work`) — outbound Discord actions are dispatched
   through the queue.
2. Start the scheduler: `php artisan schedule:work`. This runs
   `giveaways:close-expired`, `events:generate-occurrences`, and
   `events:post-due-occurrences` on their configured intervals.
3. Start the bot (`npm start` in `bot/`).
4. Invite your Discord application's bot to a test server; it will
   register the guild via `POST /internal/guilds` automatically.
5. Log into the dashboard via Discord OAuth. For giveaways: create a
   collection theme, then create and start a giveaway, and click "Join
   Giveaway" in Discord. For events: create an event role set, then create
   an event (one-off or recurring) — an occurrence is posted automatically
   (immediately for a one-off event, or on the next `events:*` scheduler
   tick for a recurring one) with a role-select menu and a Not Attending
   button.
