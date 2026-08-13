/**
 * Maps the { status, role, reason } payload returned by
 * POST /internal/event-occurrences/{id}/signups (openapi.yaml SignupResult)
 * to the ephemeral Discord reply text shown to the member.
 *
 * Pure and side-effect free, mirroring joinResultReply.js - the bot must
 * not re-implement any signup/capacity/waitlist logic, only translate
 * Laravel's decision into a message.
 */
export function eventSignupResultReply(result) {
  switch (result.status) {
    case 'confirmed':
      return `✅ You're confirmed for **${result.role.name}**.`
    case 'waitlisted':
      return `⏳ **${result.role.name}** is full - you've been added to the waitlist and will be confirmed automatically if a spot opens up.`
    case 'rejected':
      return result.reason ?? 'Sorry, that signup could not be processed.'
    case 'not_attending':
      return "Got it - you're marked as not attending."
    default:
      throw new Error(`Unknown signup result status: ${result.status}`)
  }
}
