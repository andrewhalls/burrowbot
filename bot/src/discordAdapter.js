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
    async postGiveawayMessage({ channel_id: channelId, collection_theme_name: themeName, ends_at: endsAt, description, image_url: imageUrl }) {
      const channel = await client.channels.fetch(channelId)

      const embed = new EmbedBuilder()
        .setTitle('🎁 Giveaway!')
        .setDescription(description || `Click **Join Giveaway** to enter and instantly find out what you won.`)
        .addFields(
          { name: 'Theme', value: themeName, inline: true },
          { name: 'Ends', value: endsAt ? `<t:${Math.floor(new Date(endsAt).getTime() / 1000)}:R>` : 'soon', inline: true },
        )
        .setColor(0x5865f2)

      if (imageUrl) embed.setImage(imageUrl)

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

      // Discord allows up to 10 embeds per message - one per winner (with
      // that winner's own item image) is unambiguous when winners received
      // different items. Falls back to today's single combined embed (no
      // images) for a zero-winner close or an unusually large winner count
      // (design.md Decision 2, add-collection-theme-item-images).
      if (winners.length > 0 && winners.length <= 10) {
        const embeds = winners.map((winner) => {
          const embed = new EmbedBuilder()
            .setTitle(`🎉 ${winner.username} won!`)
            .setDescription(`**${winner.item_name}**`)
            .setColor(0x57f287)

          if (winner.item_image_url) embed.setImage(winner.item_image_url)

          return embed
        })

        await channel.send({ embeds })
        return
      }

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
