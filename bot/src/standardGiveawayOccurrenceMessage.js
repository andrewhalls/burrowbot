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
/**
 * Renders the "Winners" field's value: a pending placeholder on the live
 * post (winners === undefined), "No winners this time." for a closed
 * occurrence with zero eligible entrants (winners === []), or the drawn
 * winners' mentions otherwise.
 */
function winnersFieldValue(winners) {
  if (winners === undefined) return 'Pending — drawn when this giveaway ends.'
  if (winners.length === 0) return 'No winners this time.'

  return winners.map((winner) => `<@${winner.discord_user_id}> won **${winner.item_name}**`).join('\n')
}

/**
 * Builds the embed(s) + Enter button payload for a standard giveaway
 * occurrence post. Shared by thread-mode and message-mode posting since the
 * content is identical - only where it's sent differs. Also shared by the
 * live post and the closed/ended edit: pass `winners` (and `ended: true`)
 * to render the closed state instead of the pending one (design.md
 * Decision 5).
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
  banner_image_url: bannerImageUrl,
  requires_booster: requiresBooster,
  required_role_ids: requiredRoleIds,
  prize_item_names: prizeItemNames,
}, { winners, ended = false } = {}) {
  const restrictions = []
  if (requiresBooster) restrictions.push('Server boosters only')
  if (requiredRoleIds && requiredRoleIds.length > 0) {
    restrictions.push(`Role required: ${requiredRoleIds.map((id) => `<@&${id}>`).join(', ')}`)
  }

  const embed = new EmbedBuilder()
    .setTitle(`🎁 ${title}${ended ? ' (Ended)' : ''}`)
    .setDescription(description)
    .addFields(
      { name: 'Prize(s)', value: prizeItemNames.join(', ') },
      { name: 'Ends', value: `<t:${Math.floor(new Date(endsAt).getTime() / 1000)}:R>` },
      { name: 'Winners', value: winnersFieldValue(winners) },
      ...(restrictions.length > 0 ? [{ name: 'Eligibility', value: restrictions.join('\n') }] : []),
    )
    .setColor(ended ? 0x5c5f66 : 0x5865f2)

  if (ended) embed.setFooter({ text: `ID: ${occurrenceId}` })
  if (imageUrl) embed.setImage(imageUrl)

  const embeds = bannerImageUrl ? [new EmbedBuilder().setImage(bannerImageUrl), embed] : [embed]

  if (ended) {
    return { embeds, components: [] }
  }

  const enterButton = new ButtonBuilder()
    .setCustomId(`${STANDARD_GIVEAWAY_ENTER_PREFIX}${occurrenceId}`)
    .setLabel('Enter')
    .setStyle(ButtonStyle.Primary)

  return {
    embeds,
    components: [new ActionRowBuilder().addComponents(enterButton)],
  }
}
