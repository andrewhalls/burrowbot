/**
 * Maps the { status, item } payload returned by
 * POST /internal/giveaways/{id}/entries (openapi.yaml EntryResult) to the
 * ephemeral Discord reply text shown to the entrant.
 *
 * Pure and side-effect free by design, per openspec specs/giveaway-entry -
 * "Entrant sees their result": the bot must not re-implement any join
 * logic, only translate Laravel's decision into a message.
 */
export function joinResultReply(result) {
  switch (result.status) {
    case 'won':
      return `🎉 You won **${result.item.name}**! A staff member will be in touch to hand it out.`
    case 'already_entered':
      return `You've already joined this giveaway - you won **${result.item.name}**.`
    case 'expired':
      return 'Sorry, this giveaway has already ended.'
    default:
      throw new Error(`Unknown join result status: ${result.status}`)
  }
}
