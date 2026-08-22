/**
 * Maps a DiscordOutboundAction row (openapi.yaml OutboundAction) to the
 * Discord call it represents, via an injected adapter so this mapping is
 * testable with a mocked Discord client instead of a real gateway
 * connection. The adapter is the only thing that actually talks to Discord.
 *
 * @param {{ type: string, payload: object }} action
 * @param {{
 *   postGiveawayMessage: (payload: object) => Promise<{ discordMessageId: string }>,
 *   closeGiveawayMessage: (payload: object) => Promise<void>,
 *   postEventOccurrenceThread: (payload: object) => Promise<{ discordThreadId: string }>,
 *   postEventOccurrenceMessage: (payload: object) => Promise<{ discordMessageId: string }>,
 *   postStandardGiveawayThread: (payload: object) => Promise<{ discordThreadId: string, discordMessageId: string }>,
 *   postStandardGiveawayMessage: (payload: object) => Promise<{ discordMessageId: string }>,
 *   announceStandardGiveawayWinners: (payload: object) => Promise<void>,
 *   announceGiveawayWinner: (payload: object) => Promise<void>,
 *   postBroadcastMessage: (payload: object) => Promise<{ discordMessageId: string }>,
 * }} adapter
 * @returns {Promise<{ discordMessageId?: string, discordThreadId?: string }>}
 */
export async function executeOutboundAction(action, adapter) {
  switch (action.type) {
    case 'post_giveaway_message': {
      const { discordMessageId } = await adapter.postGiveawayMessage(action.payload)

      return { discordMessageId }
    }
    case 'close_giveaway_message':
      await adapter.closeGiveawayMessage(action.payload)

      return {}
    case 'post_event_occurrence_thread': {
      const { discordThreadId } = await adapter.postEventOccurrenceThread(action.payload)

      return { discordThreadId }
    }
    case 'post_event_occurrence_message': {
      const { discordMessageId } = await adapter.postEventOccurrenceMessage(action.payload)

      return { discordMessageId }
    }
    case 'post_standard_giveaway_thread': {
      const { discordThreadId, discordMessageId } = await adapter.postStandardGiveawayThread(action.payload)

      return { discordThreadId, discordMessageId }
    }
    case 'post_standard_giveaway_message': {
      const { discordMessageId } = await adapter.postStandardGiveawayMessage(action.payload)

      return { discordMessageId }
    }
    case 'announce_standard_giveaway_winners':
      await adapter.announceStandardGiveawayWinners(action.payload)

      return {}
    case 'announce_giveaway_winner':
    case 'announce_standard_giveaway_winner':
      // Same generic "send this plain text to this channel" operation as
      // the popup giveaway per-winner message - reuses the same adapter
      // method rather than a near-identical duplicate (design.md Decision 5,
      // add-standard-giveaway-per-winner-message-and-popup-flag).
      await adapter.announceGiveawayWinner(action.payload)

      return {}
    case 'post_broadcast_message': {
      const { discordMessageId } = await adapter.postBroadcastMessage(action.payload)

      return { discordMessageId }
    }
    default:
      throw new Error(`Unknown outbound action type: ${action.type}`)
  }
}
