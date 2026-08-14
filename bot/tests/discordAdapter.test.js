import { describe, expect, it, vi } from 'vitest'
import { createDiscordAdapter } from '../src/discordAdapter.js'

function fakeClientWithChannel() {
  const channel = { send: vi.fn().mockResolvedValue({ id: 'msg-1' }) }
  const client = { channels: { fetch: vi.fn().mockResolvedValue(channel) } }

  return { client, channel }
}

describe('createDiscordAdapter - postGiveawayMessage', () => {
  it('uses the default instructional line when no description is given', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)

    await adapter.postGiveawayMessage({ channel_id: '123', collection_theme_name: 'Retro Arcade', ends_at: null })

    const embed = channel.send.mock.calls[0][0].embeds[0]
    expect(embed.data.description).toContain('Click **Join Giveaway**')
  })

  it('uses the custom description when given, instead of the default line', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)

    await adapter.postGiveawayMessage({
      channel_id: '123',
      collection_theme_name: 'Retro Arcade',
      ends_at: null,
      description: 'A very special giveaway',
    })

    const embed = channel.send.mock.calls[0][0].embeds[0]
    expect(embed.data.description).toBe('A very special giveaway')
  })

  it('sets the embed image only when image_url is present', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)

    await adapter.postGiveawayMessage({
      channel_id: '123',
      collection_theme_name: 'Retro Arcade',
      ends_at: null,
      image_url: 'https://example.test/prize.png',
    })

    const embed = channel.send.mock.calls[0][0].embeds[0]
    expect(embed.data.image.url).toBe('https://example.test/prize.png')
  })

  it('omits the embed image when image_url is absent', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)

    await adapter.postGiveawayMessage({ channel_id: '123', collection_theme_name: 'Retro Arcade', ends_at: null })

    const embed = channel.send.mock.calls[0][0].embeds[0]
    expect(embed.data.image).toBeUndefined()
  })
})

describe('createDiscordAdapter - announceStandardGiveawayWinners', () => {
  it('sends one embed per winner, with that winner\'s own item image, for a small winner count', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)
    const winners = [
      { discord_user_id: '1', display_name: 'Alice', item_id: 10, item_name: 'Joystick', item_image_url: 'https://example.test/joystick.png' },
      { discord_user_id: '2', display_name: 'Bob', item_id: 11, item_name: 'Cartridge', item_image_url: null },
    ]

    await adapter.announceStandardGiveawayWinners({ channel_id: '123', discord_thread_id: null, winners })

    const embeds = channel.send.mock.calls[0][0].embeds
    expect(embeds).toHaveLength(2)
    expect(embeds[0].data.title).toContain('Alice')
    expect(embeds[0].data.image.url).toBe('https://example.test/joystick.png')
    expect(embeds[1].data.title).toContain('Bob')
    expect(embeds[1].data.image).toBeUndefined()
  })

  it('falls back to a single combined embed when there are zero winners', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)

    await adapter.announceStandardGiveawayWinners({ channel_id: '123', discord_thread_id: null, winners: [] })

    const embeds = channel.send.mock.calls[0][0].embeds
    expect(embeds).toHaveLength(1)
    expect(embeds[0].data.description).toContain('No entrants')
  })

  it('falls back to a single combined embed when the winner count exceeds 10', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)
    const winners = Array.from({ length: 11 }, (_, i) => ({
      discord_user_id: `${i}`,
      display_name: `Winner${i}`,
      item_id: i,
      item_name: `Item${i}`,
      item_image_url: null,
    }))

    await adapter.announceStandardGiveawayWinners({ channel_id: '123', discord_thread_id: null, winners })

    const embeds = channel.send.mock.calls[0][0].embeds
    expect(embeds).toHaveLength(1)
    expect(embeds[0].data.description).toContain('<@0>')
    expect(embeds[0].data.description).toContain('<@10>')
  })
})
