import { ActionRowBuilder, ButtonBuilder, ButtonStyle, EmbedBuilder } from 'discord.js'
import { buildEventOccurrenceMessage } from './eventOccurrenceMessage.js'
import { buildStandardGiveawayOccurrenceMessage } from './standardGiveawayOccurrenceMessage.js'

export const JOIN_GIVEAWAY_BUTTON_ID = 'join-giveaway'

/**
 * Real Discord REST calls, built on a live discord.js Client. Kept thin and
 * side-effecting on purpose - all decision-making lives in
 * outboundActionExecutor.js so that logic can be unit tested without a
 * gateway connection.
 */
export function createDiscordAdapter(client) {
  return {
    async postGiveawayMessage({ channel_id: channelId, collection_theme_name: themeName, ends_at: endsAt }) {
      const channel = await client.channels.fetch(channelId)

      const embed = new EmbedBuilder()
        .setTitle('🎁 Giveaway!')
        .setDescription(`Click **Join Giveaway** to enter and instantly find out what you won.`)
        .addFields(
          { name: 'Theme', value: themeName, inline: true },
          { name: 'Ends', value: endsAt ? `<t:${Math.floor(new Date(endsAt).getTime() / 1000)}:R>` : 'soon', inline: true },
        )
        .setColor(0x5865f2)

      const button = new ButtonBuilder()
        .setCustomId(JOIN_GIVEAWAY_BUTTON_ID)
        .setLabel('Join Giveaway')
        .setStyle(ButtonStyle.Primary)

      const message = await channel.send({ embeds: [embed], components: [new ActionRowBuilder().addComponents(button)] })

      return { discordMessageId: message.id }
    },

    async closeGiveawayMessage({ channel_id: channelId, discord_message_id: discordMessageId }) {
      if (!discordMessageId) return

      const channel = await client.channels.fetch(channelId)
      const message = await channel.messages.fetch(discordMessageId)

      const closedEmbed = EmbedBuilder.from(message.embeds[0] ?? new EmbedBuilder())
        .setTitle('🎁 Giveaway ended')
        .setColor(0x5c5f66)

      await message.edit({ embeds: [closedEmbed], components: [] })
    },

    async postEventOccurrenceThread(payload) {
      const channel = await client.channels.fetch(payload.channel_id)
      const thread = await channel.threads.create({ name: payload.title })
      await thread.send(buildEventOccurrenceMessage(payload))

      return { discordThreadId: thread.id }
    },

    async postEventOccurrenceMessage(payload) {
      const channel = await client.channels.fetch(payload.channel_id)
      const message = await channel.send(buildEventOccurrenceMessage(payload))

      return { discordMessageId: message.id }
    },

    async postStandardGiveawayThread(payload) {
      const channel = await client.channels.fetch(payload.channel_id)
      const thread = await channel.threads.create({ name: payload.title })
      await thread.send(buildStandardGiveawayOccurrenceMessage(payload))

      return { discordThreadId: thread.id }
    },

    async postStandardGiveawayMessage(payload) {
      const channel = await client.channels.fetch(payload.channel_id)
      const message = await channel.send(buildStandardGiveawayOccurrenceMessage(payload))

      return { discordMessageId: message.id }
    },

    async announceStandardGiveawayWinners({ channel_id: channelId, discord_thread_id: discordThreadId, winners }) {
      const channel = await client.channels.fetch(discordThreadId ?? channelId)

      const embed = new EmbedBuilder()
        .setTitle('🎉 Winners announced!')
        .setDescription(
          winners.length > 0
            ? winners.map((winner) => `<@${winner.discord_user_id}> won **${winner.item_name}**`).join('\n')
            : 'No entrants - no winners this time.',
        )
        .setColor(0x57f287)

      await channel.send({ embeds: [embed] })
    },
  }
}
