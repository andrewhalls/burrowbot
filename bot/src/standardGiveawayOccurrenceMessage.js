import { ActionRowBuilder, ButtonBuilder, ButtonStyle, EmbedBuilder } from 'discord.js'

export const STANDARD_GIVEAWAY_ENTER_PREFIX = 'standard-giveaway-enter:'

/**
 * Builds the embed + Enter button payload for a standard giveaway occurrence
 * post. Shared by thread-mode and message-mode posting since the content is
 * identical - only where it's sent differs.
 *
 * The occurrence id is encoded directly into the button's customId (rather
 * than kept in an in-memory routing table), so interactions resolve
 * correctly even after a bot restart - Discord persists customId on
 * components indefinitely. Same pattern as event occurrences.
 */
export function buildStandardGiveawayOccurrenceMessage({
  occurrence_id: occurrenceId,
  title,
  description,
  ends_at: endsAt,
  image_url: imageUrl,
  requires_booster: requiresBooster,
  required_role_ids: requiredRoleIds,
  prize_item_names: prizeItemNames,
}) {
  const restrictions = []
  if (requiresBooster) restrictions.push('Server boosters only')
  if (requiredRoleIds && requiredRoleIds.length > 0) {
    restrictions.push(`Role required: ${requiredRoleIds.map((id) => `<@&${id}>`).join(', ')}`)
  }

  const embed = new EmbedBuilder()
    .setTitle(`🎁 ${title}`)
    .setDescription(description)
    .addFields(
      { name: 'Prize(s)', value: prizeItemNames.join(', ') },
      { name: 'Ends', value: `<t:${Math.floor(new Date(endsAt).getTime() / 1000)}:R>` },
      ...(restrictions.length > 0 ? [{ name: 'Eligibility', value: restrictions.join('\n') }] : []),
    )
    .setColor(0x5865f2)

  if (imageUrl) embed.setImage(imageUrl)

  const enterButton = new ButtonBuilder()
    .setCustomId(`${STANDARD_GIVEAWAY_ENTER_PREFIX}${occurrenceId}`)
    .setLabel('Enter')
    .setStyle(ButtonStyle.Primary)

  return {
    embeds: [embed],
    components: [new ActionRowBuilder().addComponents(enterButton)],
  }
}
