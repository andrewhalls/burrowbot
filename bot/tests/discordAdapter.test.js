import { describe, expect, it, vi } from 'vitest'
import { createDiscordAdapter } from '../src/discordAdapter.js'

function fakeClientWithChannel() {
  const channel = { send: vi.fn().mockResolvedValue({ id: 'msg-1' }) }
  const client = { channels: { fetch: vi.fn().mockResolvedValue(channel) } }

  return { client, channel }
}

function fakeClientWithEditableMessage() {
  const message = { edit: vi.fn().mockResolvedValue(undefined) }
  const channel = {
    send: vi.fn().mockResolvedValue({ id: 'msg-1' }),
    messages: { fetch: vi.fn().mockResolvedValue(message) },
  }
  const client = { channels: { fetch: vi.fn().mockResolvedValue(channel) } }

  return { client, channel, message }
}

const occurrencePayload = {
  occurrence_id: 7,
  title: 'Nitro Friday',
  description: 'Weekly booster giveaway.',
  ends_at: '2026-06-01T20:00:00Z',
  requires_booster: false,
  required_role_ids: [],
  prize_item_names: ['Golden Coin'],
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
  it('edits the original message in place when discord_message_id is present, rebuilding it from the payload', async () => {
    const { client, channel, message } = fakeClientWithEditableMessage()
    const adapter = createDiscordAdapter(client)
    const winners = [{ discord_user_id: '1', display_name: 'Alice', item_id: 10, item_name: 'Joystick', item_image_url: null }]

    await adapter.announceStandardGiveawayWinners({
      ...occurrencePayload,
      channel_id: '123',
      discord_thread_id: null,
      discord_message_id: 'original-msg',
      winners,
      congrats_message: null,
    })

    expect(channel.messages.fetch).toHaveBeenCalledWith('original-msg')
    const editedPayload = message.edit.mock.calls[0][0]
    expect(editedPayload.embeds[0].data.fields.find((f) => f.name === 'Winners').value).toContain('<@1> won **Joystick**')
    expect(editedPayload.components).toHaveLength(0)
  })

  it('sends a new congrats message after editing, only when congrats_message is present', async () => {
    const { client, channel } = fakeClientWithEditableMessage()
    const adapter = createDiscordAdapter(client)

    await adapter.announceStandardGiveawayWinners({
      ...occurrencePayload,
      channel_id: '123',
      discord_thread_id: null,
      discord_message_id: 'original-msg',
      winners: [],
      congrats_message: 'Congrats <@1>! You won Joystick.',
    })

    expect(channel.send).toHaveBeenCalledWith({ content: 'Congrats <@1>! You won Joystick.' })
  })

  it('skips the congrats message when congrats_message is null', async () => {
    const { client, channel } = fakeClientWithEditableMessage()
    const adapter = createDiscordAdapter(client)

    await adapter.announceStandardGiveawayWinners({
      ...occurrencePayload,
      channel_id: '123',
      discord_thread_id: null,
      discord_message_id: 'original-msg',
      winners: [],
      congrats_message: null,
    })

    expect(channel.send).not.toHaveBeenCalled()
  })

  it('falls back to per-winner embeds when discord_message_id is absent', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)
    const winners = [
      { discord_user_id: '1', display_name: 'Alice', item_id: 10, item_name: 'Joystick', item_image_url: 'https://example.test/joystick.png' },
      { discord_user_id: '2', display_name: 'Bob', item_id: 11, item_name: 'Cartridge', item_image_url: null },
    ]

    await adapter.announceStandardGiveawayWinners({
      ...occurrencePayload,
      channel_id: '123',
      discord_thread_id: null,
      discord_message_id: null,
      winners,
      congrats_message: null,
    })

    const embeds = channel.send.mock.calls[0][0].embeds
    expect(embeds).toHaveLength(2)
    expect(embeds[0].data.title).toContain('Alice')
    expect(embeds[0].data.image.url).toBe('https://example.test/joystick.png')
    expect(embeds[1].data.title).toContain('Bob')
    expect(embeds[1].data.image).toBeUndefined()
  })

  it('falls back to a single combined embed when there are zero winners and no discord_message_id', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)

    await adapter.announceStandardGiveawayWinners({
      ...occurrencePayload,
      channel_id: '123',
      discord_thread_id: null,
      discord_message_id: null,
      winners: [],
      congrats_message: null,
    })

    const embeds = channel.send.mock.calls[0][0].embeds
    expect(embeds).toHaveLength(1)
    expect(embeds[0].data.description).toContain('No entrants')
  })

  it('falls back to a single combined embed when the winner count exceeds 10 and no discord_message_id', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)
    const winners = Array.from({ length: 11 }, (_, i) => ({
      discord_user_id: `${i}`,
      display_name: `Winner${i}`,
      item_id: i,
      item_name: `Item${i}`,
      item_image_url: null,
    }))

    await adapter.announceStandardGiveawayWinners({
      ...occurrencePayload,
      channel_id: '123',
      discord_thread_id: null,
      discord_message_id: null,
      winners,
      congrats_message: null,
    })

    const embeds = channel.send.mock.calls[0][0].embeds
    expect(embeds).toHaveLength(1)
    expect(embeds[0].data.description).toContain('<@0>')
    expect(embeds[0].data.description).toContain('<@10>')
  })
})

describe('createDiscordAdapter - announceGiveawayWinner', () => {
  it('sends the already-rendered message as plain content to the configured channel', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)

    await adapter.announceGiveawayWinner({ channel_id: '123', message: 'Congrats <@111>! You won Golden Coin.' })

    expect(client.channels.fetch).toHaveBeenCalledWith('123')
    expect(channel.send).toHaveBeenCalledWith({ content: 'Congrats <@111>! You won Golden Coin.' })
  })
})

describe('createDiscordAdapter - postBroadcastMessage', () => {
  it('posts the already-resolved message as plain content and returns the new message id', async () => {
    const { client, channel } = fakeClientWithChannel()
    const adapter = createDiscordAdapter(client)

    const result = await adapter.postBroadcastMessage({ channel_id: '123', message: 'Reminder: raid resets in <#456> at 8:00pm.' })

    expect(client.channels.fetch).toHaveBeenCalledWith('123')
    expect(channel.send).toHaveBeenCalledWith({ content: 'Reminder: raid resets in <#456> at 8:00pm.' })
    expect(result).toEqual({ discordMessageId: 'msg-1' })
  })
})
