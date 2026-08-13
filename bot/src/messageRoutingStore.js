/**
 * In-memory discord_message_id -> giveaway_id routing table, rebuilt from
 * GET /internal/giveaways/active on every startup/reconnect so the bot
 * never re-posts an already-active giveaway.
 *
 * See openspec specs/discord-bot-gateway - "Idempotent recovery on reconnect".
 */
export function createMessageRoutingStore() {
  const giveawayIdByMessageId = new Map()

  return {
    async rebuildFromLaravel(laravelClient) {
      giveawayIdByMessageId.clear()

      const activeGiveaways = await laravelClient.listActiveGiveaways()

      for (const giveaway of activeGiveaways) {
        if (giveaway.discord_message_id) {
          giveawayIdByMessageId.set(giveaway.discord_message_id, giveaway.id)
        }
      }

      return activeGiveaways.length
    },

    remember(discordMessageId, giveawayId) {
      giveawayIdByMessageId.set(discordMessageId, giveawayId)
    },

    giveawayIdForMessage(discordMessageId) {
      return giveawayIdByMessageId.get(discordMessageId)
    },
  }
}
