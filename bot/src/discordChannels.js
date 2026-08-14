import { ChannelType } from 'discord.js'

/**
 * The only channel types a giveaway/event can actually be posted to (as a
 * new thread or a new plain message) - see openspec design.md
 * (add-discord-channel-picker) Decision 2. Everything else (voice,
 * category, forum, thread, stage, media) is filtered out here, before
 * Laravel ever sees it.
 */
const POSTABLE_CHANNEL_TYPES = new Set([ChannelType.GuildText, ChannelType.GuildAnnouncement])

/**
 * Filters a guild's channels down to postable ones and maps them to the
 * shape `laravelClient.syncGuildChannels` sends. Pure and side-effect free
 * so it's testable without a live gateway connection - callers pass in
 * `[...guild.channels.cache.values()]`, not the Collection itself.
 *
 * @param {{ id: string, name: string, type: number }[]} channels
 * @returns {{ discord_channel_id: string, name: string }[]}
 */
export function postableChannels(channels) {
  return channels
    .filter((channel) => POSTABLE_CHANNEL_TYPES.has(channel.type))
    .map((channel) => ({ discord_channel_id: channel.id, name: channel.name }))
}
