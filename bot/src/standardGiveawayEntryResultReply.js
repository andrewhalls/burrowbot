/**
 * Maps the { status, reason } payload returned by
 * POST /internal/standard-giveaway-occurrences/{id}/entries (openapi.yaml
 * StandardGiveawayEntryResult) to the ephemeral Discord reply text shown to
 * the entrant.
 *
 * Pure and side-effect free, mirroring joinResultReply.js/
 * eventSignupResultReply.js - the bot must not re-implement any
 * eligibility/entry logic, only translate Laravel's decision into a message.
 */
export function standardGiveawayEntryResultReply(result) {
  switch (result.status) {
    case 'entered':
      return "✅ You're entered! Winners will be drawn and announced when this giveaway ends."
    case 'already_entered':
      return "You've already entered this giveaway."
    case 'rejected':
      return result.reason ?? 'Sorry, you are not eligible to enter this giveaway.'
    case 'closed':
      return 'Sorry, this giveaway has already ended.'
    default:
      throw new Error(`Unknown standard giveaway entry result status: ${result.status}`)
  }
}
