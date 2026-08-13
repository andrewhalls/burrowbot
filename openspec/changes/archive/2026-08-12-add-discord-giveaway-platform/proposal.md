## Why

There is no system today for running Discord server events or pop-up giveaways without a human manually tracking entrants in a spreadsheet and DMing prizes one by one. Burrow gives server staff a Laravel-backed bot that posts a giveaway in a channel, lets members claim an entry with one click, instantly and fairly assigns each entrant a themed prize, and gives staff a searchable dashboard to fulfil what was won — across as many Discord servers (guilds) as install the bot.

## What Changes

- Stand up the Laravel application skeleton (auth, base layout, queue/worker config) needed to host every capability below.
- Add Discord OAuth login and per-guild authorization for the admin dashboard.
- Add guild registration/management so the bot can be installed to, and the dashboard can manage, multiple Discord servers.
- Add a Discord member directory, synced from Discord, used to search/filter and to attribute entries.
- Add collection themes: a "collection theme" (e.g. "Retro Arcade") with an ordered list of prize items, stored as its own MySQL collection, reusable across giveaways.
- Add giveaway lifecycle management: staff configure a channel, a collection theme, and a duration (X minutes), and start/schedule a giveaway.
- Add giveaway entry handling: a "Join Giveaway" button in the posted Discord message that enters a member exactly once, immediately assigns a random item from the giveaway's collection theme, and replies to the member (ephemerally, in Discord) with what they won. Entries are rejected once the giveaway's duration has elapsed, enforced server-side (not just by disabling the button).
- Add the persistent Discord gateway bot process contract: posting the giveaway message/embed/button, listening for the button interaction, relaying it to Laravel's internal API, editing the message when the giveaway closes, and replying to entrants.
- Add an admin giveaway dashboard screen to filter and search entrants by member (name/Discord tag/ID) and see/mark what each entrant won, to make fulfilment easy.

## Capabilities

### New Capabilities
- `auth`: Discord OAuth login for staff and per-guild admin authorization.
- `guild-management`: registering Discord guilds the bot operates in and their settings.
- `member-directory`: Discord member records per guild, synced for search/filter/attribution.
- `collection-themes`: collection themes of prize items, managed independently of any single giveaway.
- `giveaway-lifecycle`: creating, starting, and time-bounded closing of a giveaway tied to a guild, channel, and collection theme.
- `giveaway-entry`: a member joining an active giveaway and receiving a random, fairly-assigned prize.
- `discord-bot-gateway`: the contract and behavior of the persistent bot process that posts messages, listens for interactions, and relays them to/from Laravel.
- `giveaway-admin-dashboard`: the Livewire screen(s) staff use to filter/search entrants by member and track fulfilment.

### Modified Capabilities
(none — this is the initial foundation change)

## Non-goals

- No support for Discord platforms other than the gateway bot flow described here (no slash-command-only / HTTP-interactions-only deployment mode in v1).
- No automated prize *shipping*/fulfillment integration (e.g. no address collection or courier API) — the dashboard only tracks who won what and a manual "fulfilled" flag.
- No weighting/rarity system for prize items in v1 — assignment is uniformly random across the collection theme's item list (see design.md for behavior when entrants outnumber items).
- No recurring/repeating giveaways in v1 — each giveaway is a single, one-off run.
- No public-facing (non-Discord, non-staff) web page for giveaways in v1 — all entrant interaction happens inside Discord; the dashboard is staff-only.
- No editing of a giveaway's collection theme or duration after it has started.

## Impact

- New Laravel application (models, migrations, Form Requests, Actions, policies, Livewire components, routes) — greenfield, no existing code to migrate.
- New MySQL schema: `guilds`, `guild_admins` (or role mapping), `discord_members`, `collection_themes`, `collection_theme_items`, `giveaways`, `giveaway_entries`, plus Laravel's standard `users`/queue/session tables.
- New internal HTTP API surface consumed only by the bot process (service-token authenticated), documented in an OpenAPI contract.
- New external dependency: a Node.js (discord.js) bot process deployed and run alongside the Laravel app, plus a Discord application/bot token per environment.
- New queue workloads: closing a giveaway on schedule, syncing guild members, dispatching outbound Discord actions.
