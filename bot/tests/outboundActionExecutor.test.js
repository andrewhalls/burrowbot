import { describe, expect, it, vi } from 'vitest'
import { executeOutboundAction } from '../src/outboundActionExecutor.js'

describe('executeOutboundAction', () => {
  it('calls postGiveawayMessage for a post_giveaway_message action and returns the message id', async () => {
    const adapter = {
      postGiveawayMessage: vi.fn().mockResolvedValue({ discordMessageId: 'msg-1' }),
      closeGiveawayMessage: vi.fn(),
    }
    const action = { type: 'post_giveaway_message', payload: { channel_id: '123' } }

    const result = await executeOutboundAction(action, adapter)

    expect(adapter.postGiveawayMessage).toHaveBeenCalledWith(action.payload)
    expect(adapter.closeGiveawayMessage).not.toHaveBeenCalled()
    expect(result).toEqual({ discordMessageId: 'msg-1' })
  })

  it('calls closeGiveawayMessage for a close_giveaway_message action', async () => {
    const adapter = {
      postGiveawayMessage: vi.fn(),
      closeGiveawayMessage: vi.fn().mockResolvedValue(undefined),
    }
    const action = { type: 'close_giveaway_message', payload: { channel_id: '123', discord_message_id: 'msg-1' } }

    const result = await executeOutboundAction(action, adapter)

    expect(adapter.closeGiveawayMessage).toHaveBeenCalledWith(action.payload)
    expect(adapter.postGiveawayMessage).not.toHaveBeenCalled()
    expect(result).toEqual({})
  })

  it('calls postEventOccurrenceThread for a post_event_occurrence_thread action and returns the thread id', async () => {
    const adapter = {
      postEventOccurrenceThread: vi.fn().mockResolvedValue({ discordThreadId: 'thread-1' }),
      postEventOccurrenceMessage: vi.fn(),
    }
    const action = { type: 'post_event_occurrence_thread', payload: { occurrence_id: 1, channel_id: '123' } }

    const result = await executeOutboundAction(action, adapter)

    expect(adapter.postEventOccurrenceThread).toHaveBeenCalledWith(action.payload)
    expect(adapter.postEventOccurrenceMessage).not.toHaveBeenCalled()
    expect(result).toEqual({ discordThreadId: 'thread-1' })
  })

  it('calls postEventOccurrenceMessage for a post_event_occurrence_message action and returns the message id', async () => {
    const adapter = {
      postEventOccurrenceThread: vi.fn(),
      postEventOccurrenceMessage: vi.fn().mockResolvedValue({ discordMessageId: 'msg-2' }),
    }
    const action = { type: 'post_event_occurrence_message', payload: { occurrence_id: 1, channel_id: '123' } }

    const result = await executeOutboundAction(action, adapter)

    expect(adapter.postEventOccurrenceMessage).toHaveBeenCalledWith(action.payload)
    expect(adapter.postEventOccurrenceThread).not.toHaveBeenCalled()
    expect(result).toEqual({ discordMessageId: 'msg-2' })
  })

  it('calls postStandardGiveawayThread for a post_standard_giveaway_thread action and returns the thread and message ids', async () => {
    const adapter = {
      postStandardGiveawayThread: vi.fn().mockResolvedValue({ discordThreadId: 'thread-2', discordMessageId: 'msg-in-thread' }),
      postStandardGiveawayMessage: vi.fn(),
      announceStandardGiveawayWinners: vi.fn(),
    }
    const action = { type: 'post_standard_giveaway_thread', payload: { occurrence_id: 1, channel_id: '123' } }

    const result = await executeOutboundAction(action, adapter)

    expect(adapter.postStandardGiveawayThread).toHaveBeenCalledWith(action.payload)
    expect(adapter.postStandardGiveawayMessage).not.toHaveBeenCalled()
    expect(result).toEqual({ discordThreadId: 'thread-2', discordMessageId: 'msg-in-thread' })
  })

  it('calls postStandardGiveawayMessage for a post_standard_giveaway_message action and returns the message id', async () => {
    const adapter = {
      postStandardGiveawayThread: vi.fn(),
      postStandardGiveawayMessage: vi.fn().mockResolvedValue({ discordMessageId: 'msg-3' }),
      announceStandardGiveawayWinners: vi.fn(),
    }
    const action = { type: 'post_standard_giveaway_message', payload: { occurrence_id: 1, channel_id: '123' } }

    const result = await executeOutboundAction(action, adapter)

    expect(adapter.postStandardGiveawayMessage).toHaveBeenCalledWith(action.payload)
    expect(adapter.postStandardGiveawayThread).not.toHaveBeenCalled()
    expect(result).toEqual({ discordMessageId: 'msg-3' })
  })

  it('calls announceStandardGiveawayWinners for an announce_standard_giveaway_winners action', async () => {
    const adapter = {
      announceStandardGiveawayWinners: vi.fn().mockResolvedValue(undefined),
    }
    const action = { type: 'announce_standard_giveaway_winners', payload: { occurrence_id: 1, channel_id: '123', winners: [] } }

    const result = await executeOutboundAction(action, adapter)

    expect(adapter.announceStandardGiveawayWinners).toHaveBeenCalledWith(action.payload)
    expect(result).toEqual({})
  })

  it('calls announceGiveawayWinner for an announce_giveaway_winner action', async () => {
    const adapter = {
      announceGiveawayWinner: vi.fn().mockResolvedValue(undefined),
    }
    const action = { type: 'announce_giveaway_winner', payload: { channel_id: '123', message: 'Congrats <@111>!' } }

    const result = await executeOutboundAction(action, adapter)

    expect(adapter.announceGiveawayWinner).toHaveBeenCalledWith(action.payload)
    expect(result).toEqual({})
  })

  it('calls postBroadcastMessage for a post_broadcast_message action and returns the message id', async () => {
    const adapter = {
      postBroadcastMessage: vi.fn().mockResolvedValue({ discordMessageId: 'msg-4' }),
    }
    const action = { type: 'post_broadcast_message', payload: { channel_id: '123', message: 'Hello!' } }

    const result = await executeOutboundAction(action, adapter)

    expect(adapter.postBroadcastMessage).toHaveBeenCalledWith(action.payload)
    expect(result).toEqual({ discordMessageId: 'msg-4' })
  })

  it('throws on an unknown action type without calling either adapter method', async () => {
    const adapter = { postGiveawayMessage: vi.fn(), closeGiveawayMessage: vi.fn() }

    await expect(executeOutboundAction({ type: 'mystery', payload: {} }, adapter)).rejects.toThrow()
    expect(adapter.postGiveawayMessage).not.toHaveBeenCalled()
    expect(adapter.closeGiveawayMessage).not.toHaveBeenCalled()
  })
})
