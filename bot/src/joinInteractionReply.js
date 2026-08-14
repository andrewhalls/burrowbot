import { EmbedBuilder } from 'discord.js'
import { joinResultReply } from './joinResultReply.js'

/**
 * Builds the options passed to `interaction.reply(...)` for a "Join
 * Giveaway" click. A new win (`status: 'won'`) is announced publicly to
 * drive hype - everyone else in the channel sees it happen, not just the
 * winner - while a duplicate entry or a too-late click stays exactly as
 * private/ephemeral as it always has been. The branch is on `result.status`,
 * never on whether the item has an image (design.md Decision 1,
 * add-collection-theme-item-images) - a public win reply happens either way,
 * the image just changes *how* it's rendered (embed vs. plain text).
 *
 * `joinResultReply` itself is untouched: its existing `won` text already
 * reads correctly as a public announcement once the winner's mention is
 * prepended.
 */
export function buildJoinInteractionReplyOptions(result, discordUserId) {
  if (result.status !== 'won') {
    return { content: joinResultReply(result), ephemeral: true }
  }

  const content = `<@${discordUserId}> ${joinResultReply(result)}`
  const imageUrl = result.item?.image_url

  if (!imageUrl) {
    return { content, ephemeral: false }
  }

  const embed = new EmbedBuilder().setDescription(content).setImage(imageUrl).setColor(0x57f287)

  return { embeds: [embed], ephemeral: false }
}
