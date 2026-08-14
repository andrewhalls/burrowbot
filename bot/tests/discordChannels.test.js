import { ChannelType } from 'discord.js'
import { describe, expect, it } from 'vitest'
import { postableChannels } from '../src/discordChannels.js'

describe('postableChannels', () => {
  it('includes text and announcement channels', () => {
    const channels = [
      { id: '1', name: 'general', type: ChannelType.GuildText },
      { id: '2', name: 'announcements', type: ChannelType.GuildAnnouncement },
    ]

    expect(postableChannels(channels)).toEqual([
      { discord_channel_id: '1', name: 'general' },
      { discord_channel_id: '2', name: 'announcements' },
    ])
  })

  it('excludes voice, category, forum, and thread channels', () => {
    const channels = [
      { id: '1', name: 'voice-chat', type: ChannelType.GuildVoice },
      { id: '2', name: 'Category', type: ChannelType.GuildCategory },
      { id: '3', name: 'forum', type: ChannelType.GuildForum },
      { id: '4', name: 'a-thread', type: ChannelType.PublicThread },
      { id: '5', name: 'stage', type: ChannelType.GuildStageVoice },
    ]

    expect(postableChannels(channels)).toEqual([])
  })

  it('returns an empty list for a guild with no channels', () => {
    expect(postableChannels([])).toEqual([])
  })
})
