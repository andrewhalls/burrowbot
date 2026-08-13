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
    default:
      throw new Error(`Unknown outbound action type: ${action.type}`)
  }
}
