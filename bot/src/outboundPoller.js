import { executeOutboundAction } from './outboundActionExecutor.js'

/**
 * Polls GET /internal/outbound-actions on an interval and executes each
 * pending action, ack'ing or fail'ing it back to Laravel so its queue's
 * retry/backoff can take over on failure (design.md Decision 1).
 */
export function startOutboundPoller({ laravelClient, adapter, intervalMs, logger = console, onMessagePosted }) {
  let cursor
  let stopped = false

  async function tick() {
    if (stopped) return

    try {
      const actions = await laravelClient.listOutboundActions(cursor)

      for (const action of actions) {
        cursor = action.id

        try {
          const { discordMessageId, discordThreadId } = await executeOutboundAction(action, adapter)

          if (discordMessageId && onMessagePosted) {
            onMessagePosted(action.giveaway_id, discordMessageId)
          }

          await laravelClient.ackOutboundAction(action.id, { discordMessageId, discordThreadId })
        } catch (error) {
          logger.error(`Outbound action ${action.id} (${action.type}) failed:`, error)
          await laravelClient.failOutboundAction(action.id, String(error.message ?? error)).catch((ackError) => {
            logger.error(`Failed to report failure for outbound action ${action.id}:`, ackError)
          })
        }
      }
    } catch (error) {
      logger.error('Failed to poll outbound actions:', error)
    }

    if (!stopped) setTimeout(tick, intervalMs)
  }

  tick()

  return {
    stop() {
      stopped = true
    },
  }
}
