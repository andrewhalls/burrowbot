## Context

See proposal.md - Why. Relevant existing pieces this design builds on:

- The bot already has a `GuildCreate` handler (`bot/src/index.js`) calling `laravelClient.guildJoined(...)` - a direct, bot-initiated call to Laravel, the same shape channel sync needs (as opposed to the outbound-actions poll loop, which is for *Laravel-initiated* Discord actions - channel sync is the reverse direction, so it follows `guildJoined`/`upsertMember`'s pattern, not the outbound-action pattern).
- `App\Actions\Guilds\SyncGuildAction`/`App\Actions\Members\SyncDiscordMemberAction` establish this codebase's "sync Action, called from an internal controller" shape.
- The bot's `Client` is constructed with `intents: [GatewayIntentBits.Guilds]`. Per Discord's gateway intent documentation, the `GUILDS` intent already delivers `GUILD_CREATE` (which includes the guild's full channel list), `CHANNEL_CREATE`, `CHANNEL_UPDATE`, and `CHANNEL_DELETE` - no new intent needed.
- `CreateStandardGiveaway`'s `prizeItemSearch`/`getSearchResultsProperty()` is this codebase's existing searchable-list pattern, but it's a live server-round-trip search over a large, growing dataset (collection theme items). A guild's channel count is small (tens, not thousands) - the picker doesn't need that pattern; see Decision 3.
- `Guild.default_channel_id` already exists as a plain string column (proposal.md - Non-goals: unchanged).

## Goals / Non-Goals

**Goals:**
- One synced source of truth for a guild's postable channels in Laravel.
- One reusable picker component wired into all 4 existing channel fields, and available for any future one.
- Self-healing sync (a missed event doesn't cause permanent drift).

**Non-Goals:**
- No channel management (create/rename/delete) from Burrow (proposal.md).
- No guaranteed-instant sync (proposal.md) - real-time events plus a periodic fallback is enough.
- No change to `guilds.default_channel_id`'s storage shape.

## Decisions

### Decision 1: One idempotent "full channel list" sync endpoint, not per-event create/update/delete endpoints
`PUT /internal/guilds/{guild}/channels`, body `{ channels: [{ discord_channel_id, name }, ...] }` - Laravel replaces that guild's entire synced channel set to match exactly what's given (upsert everything present, delete anything not present). The bot calls this with its **current, already-in-sync `guild.channels.cache`** (discord.js keeps this cache correct in real time as gateway events arrive - no extra Discord API call needed) on:
- `GuildCreate` (initial sync)
- Any `ChannelCreate`/`ChannelUpdate`/`ChannelDelete` event for that guild (recompute and resend the full list, not a single-channel patch)
- A periodic timer per guild (fallback safety net, matching `startOutboundPoller`'s interval-driven shape but on a much longer cadence - minutes, not seconds, since this is a fallback, not the primary path)

**Alternative considered**: separate `POST`/`PATCH`/`DELETE` endpoints mirroring each Discord event 1:1. Rejected - three endpoints to keep in sync with three gateway events is more surface area than one idempotent "here's the truth right now" endpoint, and the full-list approach is self-healing: if an event is ever missed (bot briefly disconnected), the next event *or* the periodic timer naturally corrects any drift without needing a separate reconciliation job.

### Decision 2: Filter to postable channel types bot-side, before ever calling Laravel
discord.js exposes `ChannelType.GuildText` (0) and `ChannelType.GuildAnnouncement` (5) as the only channel types a giveaway/event can be posted to as a new thread or plain message. The bot filters `guild.channels.cache` to just these two types before building the payload - Laravel never sees (and never has to filter out) voice channels, categories, forums, or threads.

**Alternative considered**: send every channel type and filter in Laravel. Rejected - the bot already has to read `channel.type` to build the payload; filtering there means Laravel's schema/queries never need to know about irrelevant Discord channel types at all.

### Decision 3: The picker is a client-side-filtered list, not a server-round-trip search
A guild's channel count is small (tens, occasionally low hundreds) - unlike `CreateStandardGiveaway`'s prize-item search (a `whereLike` query over a potentially large, growing dataset), the picker can render the full synced list once and filter it in the browser as the admin types, with zero additional network round-trips per keystroke. Implemented as a Blade component (`<x-channel-picker :guild="$guild" model="channelId" />`, rendering a text input + a filtered option list via a small vanilla JS filter, no framework dependency) whose selected channel's Discord ID is written into a hidden input carrying the real `wire:model` binding back to whichever parent Livewire component embeds it (`CreateGiveaway`, `CreateStandardGiveaway`, `CreateEvent`, `GuildSettings` each keep their own `channelId`/`defaultChannelId` property unchanged - only the input markup changes).

**Alternative considered**: a dedicated Livewire child component with two-way binding to the parent. Rejected for this - introducing Livewire nested-component data binding would be a new pattern for this codebase; a plain Blade component whose hidden input carries the parent's own existing `wire:model` name keeps every parent's Livewire property/validation untouched and requires no new binding mechanism.

## Risks / Trade-offs

- **[Risk]** A guild with zero synced channels yet (bot just joined, sync request still in flight) shows an empty picker. → **Mitigation**: acceptable - the same guild would have shown a blank text field before; this is strictly an improvement, and sync happens within moments of the bot joining.
- **[Risk]** The full-list resync on every single channel event means a guild with frequent channel churn sends more requests than a targeted patch would. → **Mitigation**: channel creation/renaming is inherently a rare, human-driven action, not a hot path - this is negligible traffic even for an active server.

## Migration Plan

One new migration (`discord_channels` table: `guild_id`, `discord_channel_id`, `name`, unique on `(guild_id, discord_channel_id)`, cascade-delete with guild). No backfill - existing guilds simply have zero synced channels until the bot's next `GuildCreate`-equivalent sync pass; since the bot is already connected to every guild it's in, a one-time full resync of all currently-connected guilds on bot restart/deploy naturally backfills this (the bot process restarting is part of every deploy per `deploy.sh`, which already expects the bot to be restarted after a deploy that changes bot code).
