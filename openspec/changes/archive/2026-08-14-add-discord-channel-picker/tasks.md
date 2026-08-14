## 1. Channel sync schema (Laravel)

- [ ] 1.1 Migration + model + factory for `discord_channels` (`guild_id` FK cascade-delete, `discord_channel_id`, `name`, unique on `(guild_id, discord_channel_id)`)
- [ ] 1.2 `Guild::channels(): HasMany<DiscordChannel>` relation
- [ ] 1.3 `App\Actions\Discord\SyncGuildChannelsAction`: given a guild and a list of `{discord_channel_id, name}`, upserts each and deletes any synced channel not in the given list (design.md Decision 1)
- [ ] 1.4 `PUT /internal/guilds/{guild}/channels` endpoint (Form Request validating the channels array) calling the sync Action
- [ ] 1.5 Pest tests: syncing creates channels; re-syncing with a changed name updates it; re-syncing with a channel removed from the list deletes it; syncing is guild-scoped (never touches another guild's channels); unauthenticated request rejected (bot.auth)

## 2. Channel sync (bot process)

- [x] 2.1 `laravelClient.syncGuildChannels(discordGuildId, channels)`: `PUT /internal/guilds/{id}/channels`
- [x] 2.2 Pure function `postableChannels(guildChannelsCache)` filtering to `ChannelType.GuildText`/`ChannelType.GuildAnnouncement` and mapping to `{discord_channel_id, name}` (design.md Decision 2) - kept pure/exported for Vitest coverage without a live gateway connection
- [x] 2.3 Wire channel sync into the existing `GuildCreate` handler (alongside the existing `guildJoined` call), and add `ChannelCreate`/`ChannelUpdate`/`ChannelDelete` handlers that recompute and resend that guild's full postable-channel list
- [x] 2.4 Periodic per-guild resync timer (fallback safety net; long interval, e.g. every 30 minutes - design.md Decision 1)
- [x] 2.5 Vitest coverage for `postableChannels` (includes text/announcement, excludes voice/category/forum/thread) and for the sync call being made with the correctly-filtered payload on each trigger (mocked Discord client, matching this codebase's existing bot test style)

## 3. Channel picker UI

- [x] 3.1 `resources/views/components/channel-picker.blade.php`: text input + client-side-filtered list of a guild's synced channels (name shown, Discord ID as the value), a hidden input carrying the real `wire:model` binding back to the parent Livewire component (design.md Decision 3)
- [x] 3.2 Replace the plain `channelId`/`defaultChannelId` text input with `<x-channel-picker>` in `CreateGiveaway`, `CreateStandardGiveaway`, `CreateEvent`, and `GuildSettings` - no changes to any of their own Livewire properties, validation, or Actions (the field still ends up a plain Discord channel ID string)
- [x] 3.3 Pest/Livewire tests: the picker on each of the 4 forms shows that guild's synced channels by name and not another guild's; selecting a channel sets the underlying field to its Discord ID; a form with zero synced channels shows an empty (not broken) picker

## 4. Documentation

- [x] 4.1 Add `PUT /internal/guilds/{guildId}/channels` to `openapi.yaml`, lint clean

## 5. Verification

- [x] 5.1 Full Pest suite passes
- [x] 5.2 Full bot Vitest suite passes
- [x] 5.3 `openspec validate add-discord-channel-picker --strict` passes
