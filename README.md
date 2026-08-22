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

### Standard giveaway recurrence rules & scheduled commands

Standard giveaways (the non-time-limited, category/eligibility-restricted
"weekly giveaway" style, as opposed to the pop-up giveaway platform) reuse
the same `RRULE` recurrence engine and structured picker as events. `null`
means a one-off giveaway.

Three scheduled commands, registered in `routes/console.php`, drive the
lifecycle:

- `standard-giveaways:generate-occurrences` (hourly) — expands each active
  recurring giveaway's rule up to a rolling window and creates any missing
  `standard_giveaway_occurrences` rows, snapshotting the giveaway's current
  prize items and required roles at generation time.
- `standard-giveaways:post-due-occurrences` (every minute) — enqueues the
  outbound action that posts each `scheduled` occurrence to Discord (as a
  new thread or a new plain message, per the giveaway's posting mode) and
  stamps `posted_at`/`ends_at`.
- `standard-giveaways:close-expired` (every minute) — closes each `posted`
  occurrence once `ends_at` has passed, draws winners, assigns prize items,
  and enqueues the "announce winners" outbound action.

Run them manually while developing:

```bash
php artisan standard-giveaways:generate-occurrences
php artisan standard-giveaways:post-due-occurrences
php artisan standard-giveaways:close-expired
```

### Broadcast recurrence rules & scheduled commands

Broadcasts (repeatable text messages posted to a Discord channel on a
schedule, e.g. "every Wednesday, remind #general about raid reset") reuse the
same `RRULE` recurrence engine and structured picker as events and standard
giveaways. `null` means a one-off broadcast. A broadcast's message template
supports a fixed set of mail-merge placeholders - `{{guild_name}}`,
`{{channel}}`, `{{date}}`, `{{time}}`, `{{next_occurrence_date}}` - resolved
at the moment each occurrence is actually posted, not when it was generated.

Two scheduled commands, registered in `routes/console.php`, drive the
lifecycle (broadcasts have no "ends"/closing step - a posted message is
simply done):

- `broadcasts:generate-occurrences` (hourly) — expands each active recurring
  broadcast's rule up to a 90-day rolling window and creates any missing
  `broadcast_occurrences` rows.
- `broadcasts:post-due-occurrences` (every minute) — resolves the occurrence's
  message template placeholders and enqueues the outbound action that posts
  the result as a new plain Discord message.

Run them manually while developing:

```bash
php artisan broadcasts:generate-occurrences
php artisan broadcasts:post-due-occurrences
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
   (`php artisan queue:work --queue=discord-outbound,default`) — outbound
   Discord actions are dispatched onto the named `discord-outbound` queue
   (`config/discord.php`'s `outbound_queue`, overridable via
   `DISCORD_OUTBOUND_QUEUE`), not Laravel's default queue, so a bare
   `queue:work` with no `--queue` flag will never pick them up.
2. Start the scheduler: `php artisan schedule:work`. This runs
   `giveaways:close-expired`, `events:generate-occurrences`,
   `events:post-due-occurrences`, `standard-giveaways:generate-occurrences`,
   `standard-giveaways:post-due-occurrences`,
   `standard-giveaways:close-expired`, `broadcasts:generate-occurrences`,
   and `broadcasts:post-due-occurrences` on their configured intervals.
3. Start the bot (`npm start` in `bot/`).
4. Invite your Discord application's bot to a test server; it will
   register the guild via `POST /internal/guilds` automatically.
5. Log into the dashboard via Discord OAuth. For giveaways: create a
   collection theme, then create and start a giveaway, and click "Join
   Giveaway" in Discord. For events: create an event role set, then create
   an event (one-off or recurring) — an occurrence is posted automatically
   (immediately for a one-off event, or on the next `events:*` scheduler
   tick for a recurring one) with a role-select menu and a Not Attending
   button. For broadcasts: create a broadcast with a message template and a
   channel — an occurrence is posted automatically the same way, with its
   placeholders resolved at post time.

## Deploying

[`deploy.sh`](deploy.sh) automates everything a deploy needs *except*
starting or restarting the long-running processes themselves (php-fpm/nginx,
the scheduler, and the bot) — those stay under whatever process manager you
use (systemd, Supervisor, pm2, etc.) so this script is safe to re-run on
every deploy without touching them:

```bash
./deploy.sh
```

It runs, in order: `composer install --no-dev`, the dashboard's frontend
build (`npm ci && npm run build`), the bot's production dependency install
(`cd bot && npm ci --omit=dev`), `php artisan migrate --force`,
`storage:link`, config/route/view caching, and `queue:restart` (which
doesn't start a worker — it just signals already-running queue workers to
pick up the new code after their current job).

Before the first deploy on a new server:

1. Check out the code (via `git pull`, a CI artifact, `rsync` — whatever
   your pipeline uses; `deploy.sh` assumes this already happened).
2. Create and fill in `.env` (copy from `.env.example`) and `bot/.env` (copy
   from `bot/.env.example`) with production values — `deploy.sh` never
   writes either file. Make sure `BOT_SERVICE_TOKEN` matches between the two.
3. Run `php artisan key:generate --force` if `APP_KEY` isn't already set.
4. Run `./deploy.sh`.
5. Start the Laravel app under php-fpm/nginx (or equivalent), the scheduler
   cron entry, the queue worker, and the bot process — see below for all
   three.

On every subsequent deploy, steps 4–5 collapse to just `./deploy.sh` plus a
reload of php-fpm/nginx and the process manager picking up the bot's new
code (most managers restart on their own schedule/health check; force one if
yours doesn't).

## Production process management

Three things need to stay running continuously in production, and each has
a different natural fit:

| Process | What it does | Recommended tool |
|---|---|---|
| Laravel scheduler | fires `events:*`/`standard-giveaways:*`/`broadcasts:*`/`giveaways:close-expired` on their configured intervals | **cron** (not `schedule:work`) |
| Queue worker (`queue:work`) | executes outbound Discord actions | **Supervisor** |
| Bot process (`bot/`, Node) | the only thing that talks to Discord | **pm2** |

`schedule:work` (used in local dev above) runs in the foreground and dies
with your terminal — fine for development, not for production. Supervisor
and pm2 are the choices below because they're each the tool their own
ecosystem already expects: Supervisor is what Laravel's own docs recommend
for `queue:work`, and pm2 is built specifically for exactly this
(auto-restart, log rotation, boot persistence) with zero PHP-specific setup
needed. If you'd rather run one tool for everything instead of two, systemd
unit files work equally well for all three and ship with any modern Linux
distro already — see the alternative at the bottom of this section.

### 1. Scheduler: cron

Add one line to the deploy user's crontab (`crontab -e`):

```cron
* * * * * cd /path/to/burrow && php artisan schedule:run >> /dev/null 2>&1
```

Laravel's scheduler itself decides which of the registered commands
(`routes/console.php`) are actually due each minute — this single cron
entry is all production ever needs, regardless of how many scheduled
commands the app has.

### 2. Queue worker: Supervisor

Install Supervisor (`apt install supervisor` on Debian/Ubuntu), then create
`/etc/supervisor/conf.d/burrow-queue.conf`:

```ini
[program:burrow-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/burrow/artisan queue:work --queue=discord-outbound,default --sleep=3 --tries=3 --max-time=3600
directory=/path/to/burrow
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/burrow/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start burrow-queue:*
```

`--max-time=3600` makes the worker exit cleanly once an hour; Supervisor
immediately restarts it, which is Laravel's own recommended way to pick up
new code after a deploy without a manual restart step (paired with
`deploy.sh`'s `queue:restart`, which asks in-flight workers to finish their
current job before that happens, rather than killing them mid-job).

### 3. Bot process: pm2

Install pm2 globally (`npm install -g pm2`), then from `bot/`:

```bash
pm2 start src/index.js --name burrow-bot
pm2 save
pm2 startup   # prints a command to run once, so pm2 restarts on server reboot
```

To pick up new bot code after a deploy:

```bash
pm2 restart burrow-bot
```

Useful pm2 commands while operating it: `pm2 logs burrow-bot`,
`pm2 status`, `pm2 monit`. pm2 restarts the process automatically if it
crashes; no extra config needed for that.

### Alternative: systemd for all three

If you'd rather manage everything with one tool, a systemd unit per
process works too. Example for the bot (`/etc/systemd/system/burrow-bot.service`):

```ini
[Unit]
Description=Burrow Discord bot
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/burrow/bot
ExecStart=/usr/bin/node src/index.js
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now burrow-bot
sudo systemctl restart burrow-bot   # after a deploy
```

The queue worker follows the same shape (`ExecStart=/usr/bin/php
/path/to/burrow/artisan queue:work --queue=discord-outbound,default --sleep=3
--tries=3`) — same `--queue` requirement as the Supervisor version above.
The scheduler
still uses cron either way — it's a one-shot command that runs every
minute and exits, not a long-running process, so it was never a systemd-vs-cron
choice to begin with.
