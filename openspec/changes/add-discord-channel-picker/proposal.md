## Why

Every place that needs a Discord channel (creating a popup giveaway, a standard giveaway, an event, and Guild Settings' default channel) is a plain text input where an admin must manually type a raw Discord channel ID with no validation, no channel name shown, and no way to browse what's actually available. Laravel has no record of a guild's channels at all today - only the bot process, connected to Discord's gateway, can see them.

## What Changes

- Sync each guild's postable channels (text and announcement channels only - not voice, categories, forums, or threads) from Discord into Laravel: on the bot joining a guild, on a periodic re-sync, and in real time as channels are created/renamed/deleted (the bot's existing `GatewayIntentBits.Guilds` intent already delivers these events - no new intent needed).
- Add one reusable, searchable channel-picker Livewire component and wire it into every existing channel-selection field: popup giveaway creation, standard giveaway creation, event creation, and Guild Settings' default channel. Any future feature needing a channel uses the same component.
- The picker only offers channels Burrow has synced for that guild (i.e. real, currently-existing, postable channels) - typing an arbitrary ID is no longer possible.

## Capabilities

### New Capabilities
- `discord-channels`: syncing a guild's postable Discord channels into Laravel, and the searchable-picker UI contract every channel-selecting form uses.

### Modified Capabilities
(none - `giveaway-lifecycle`, `events`, `standard-giveaways` already just say "specifying a Discord channel," which remains true; this change is about *how* that channel is selected, not a change to those requirements themselves.)

## Impact

- **Affected code**: `bot/src/index.js` (channel sync on `GuildCreate`/`ChannelCreate`/`ChannelUpdate`/`ChannelDelete`), `bot/src/laravelClient.js` (new sync call), a new internal endpoint + migration/model/Action in Laravel, a new `App\Livewire\Discord\ChannelPicker` (or similar) component, and the 4 existing forms (`CreateGiveaway`, `CreateStandardGiveaway`, `CreateEvent`, `GuildSettings`) switching their plain `channelId` text input to it.
- **No changes** to `giveaway-lifecycle`, `events`, `standard-giveaways`, or `discord-bot-gateway`'s posting contract - a channel ID is still just a channel ID once selected; only how an admin picks it changes.
- **Multi-guild scoping**: synced channels are guild-scoped exactly like every other guild-owned resource; the picker only ever queries the current guild's own channels.

## Non-goals

- No channel creation, renaming, or deletion from Burrow - Discord is the sole source of truth; Burrow only mirrors what the bot reports.
- No guaranteed zero-delay reflection of a Discord-side channel rename - periodic re-sync plus real-time gateway events is enough; a few minutes of staleness in the worst case (bot restart before a periodic sync runs) is acceptable.
- No migration of the `guilds.default_channel_id` column's storage shape - it stays a plain Discord channel ID string; the picker just validates it against the synced channel list on save instead of accepting anything.
