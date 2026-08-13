import { ActionRowBuilder, ButtonBuilder, ButtonStyle, EmbedBuilder, StringSelectMenuBuilder } from 'discord.js'

export const EVENT_ROLE_SELECT_PREFIX = 'event-role-select:'
export const EVENT_NOT_ATTENDING_PREFIX = 'event-not-attending:'

/**
 * Builds the embed + role-select-menu + Not-Attending-button payload for an
 * event occurrence post. Shared by thread-mode and message-mode posting
 * since the content is identical - only where it's sent differs.
 *
 * The occurrence id is encoded directly into each component's customId
 * (rather than kept in an in-memory routing table), so interactions
 * resolve correctly even after a bot restart - Discord persists customId
 * on components indefinitely.
 */
export function buildEventOccurrenceMessage({ occurrence_id: occurrenceId, title, description, scheduled_start_at: scheduledStartAt, roles }) {
  const embed = new EmbedBuilder()
    .setTitle(`📅 ${title}`)
    .setDescription(description)
    .addFields({
      name: 'Starts',
      value: `<t:${Math.floor(new Date(scheduledStartAt).getTime() / 1000)}:F>`,
    })
    .setColor(0x5865f2)

  const roleSelect = new StringSelectMenuBuilder()
    .setCustomId(`${EVENT_ROLE_SELECT_PREFIX}${occurrenceId}`)
    .setPlaceholder('Select your role')
    .addOptions(roles.map((role) => ({ label: role.name, value: String(role.id) })))

  const notAttendingButton = new ButtonBuilder()
    .setCustomId(`${EVENT_NOT_ATTENDING_PREFIX}${occurrenceId}`)
    .setLabel('Not Attending')
    .setStyle(ButtonStyle.Secondary)

  return {
    embeds: [embed],
    components: [
      new ActionRowBuilder().addComponents(roleSelect),
      new ActionRowBuilder().addComponents(notAttendingButton),
    ],
  }
}
