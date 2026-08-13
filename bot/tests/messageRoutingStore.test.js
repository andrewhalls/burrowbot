import { describe, expect, it } from 'vitest'
import { createMessageRoutingStore } from '../src/messageRoutingStore.js'

describe('createMessageRoutingStore', () => {
  it('rebuilds the routing table from active giveaways reported by Laravel', async () => {
    const store = createMessageRoutingStore()
    const laravelClient = {
      listActiveGiveaways: async () => [
        { id: 1, discord_message_id: 'msg-1' },
        { id: 2, discord_message_id: 'msg-2' },
        { id: 3, discord_message_id: null }, // not yet posted - skipped
      ],
    }

    const count = await store.rebuildFromLaravel(laravelClient)

    expect(count).toBe(3)
    expect(store.giveawayIdForMessage('msg-1')).toBe(1)
    expect(store.giveawayIdForMessage('msg-2')).toBe(2)
  })

  it('remembers a newly posted message without a full rebuild', () => {
    const store = createMessageRoutingStore()

    store.remember('msg-9', 9)

    expect(store.giveawayIdForMessage('msg-9')).toBe(9)
  })

  it('returns undefined for an unknown message id', () => {
    const store = createMessageRoutingStore()

    expect(store.giveawayIdForMessage('unknown')).toBeUndefined()
  })

  it('clears stale entries on rebuild', async () => {
    const store = createMessageRoutingStore()
    store.remember('stale-msg', 1)

    await store.rebuildFromLaravel({ listActiveGiveaways: async () => [] })

    expect(store.giveawayIdForMessage('stale-msg')).toBeUndefined()
  })
})
