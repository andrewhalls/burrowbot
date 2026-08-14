/**
 * Thin wrapper around Burrow's internal Laravel API (openapi.yaml at the
 * repo root). This is the ONLY place the bot talks to Laravel - see
 * openspec design.md Decision 1: the bot never touches MySQL directly.
 */
export function createLaravelClient({ baseUrl, serviceToken, fetchImpl = fetch }) {
  async function request(path, options = {}) {
    const response = await fetchImpl(`${baseUrl}${path}`, {
      ...options,
      headers: {
        Authorization: `Bearer ${serviceToken}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...options.headers,
      },
    })

    if (!response.ok) {
      const body = await response.text().catch(() => '')
      throw new Error(`Laravel API ${options.method ?? 'GET'} ${path} failed: ${response.status} ${body}`)
    }

    if (response.status === 204) return null

    return response.json()
  }

  return {
    guildJoined(discordGuildId, name) {
      return request('/internal/guilds', {
        method: 'POST',
        body: JSON.stringify({ discord_guild_id: discordGuildId, name }),
      })
    },

    updateGuild(discordGuildId, attributes) {
      return request(`/internal/guilds/${discordGuildId}`, {
        method: 'PATCH',
        body: JSON.stringify(attributes),
      })
    },

    upsertMember(discordGuildId, discordUserId, username, avatarUrl) {
      return request(`/internal/guilds/${discordGuildId}/members/${discordUserId}`, {
        method: 'PUT',
        body: JSON.stringify({ username, avatar_url: avatarUrl }),
      })
    },

    syncGuildChannels(discordGuildId, channels) {
      return request(`/internal/guilds/${discordGuildId}/channels`, {
        method: 'PUT',
        body: JSON.stringify({ channels }),
      })
    },

    syncGuildRoles(discordGuildId, roles) {
      return request(`/internal/guilds/${discordGuildId}/roles`, {
        method: 'PUT',
        body: JSON.stringify({ roles }),
      })
    },

    joinGiveaway(giveawayId, discordUserId, discordUsername) {
      return request(`/internal/giveaways/${giveawayId}/entries`, {
        method: 'POST',
        body: JSON.stringify({ discord_user_id: discordUserId, discord_username: discordUsername }),
      })
    },

    listActiveGiveaways() {
      return request('/internal/giveaways/active')
    },

    listOutboundActions(since) {
      const query = since ? `?since=${encodeURIComponent(since)}` : ''

      return request(`/internal/outbound-actions${query}`)
    },

    ackOutboundAction(id, { discordMessageId, discordThreadId } = {}) {
      const body = {}
      if (discordMessageId) body.discord_message_id = discordMessageId
      if (discordThreadId) body.discord_thread_id = discordThreadId

      return request(`/internal/outbound-actions/${id}/ack`, {
        method: 'POST',
        body: JSON.stringify(body),
      })
    },

    failOutboundAction(id, reason) {
      return request(`/internal/outbound-actions/${id}/fail`, {
        method: 'POST',
        body: JSON.stringify({ reason }),
      })
    },

    signUpForEventOccurrence(occurrenceId, discordUserId, discordUsername, eventRoleId) {
      return request(`/internal/event-occurrences/${occurrenceId}/signups`, {
        method: 'POST',
        body: JSON.stringify({
          discord_user_id: discordUserId,
          discord_username: discordUsername,
          ...(eventRoleId ? { event_role_id: eventRoleId } : {}),
        }),
      })
    },

    submitStandardGiveawayEntry(occurrenceId, discordUserId, discordUsername, discordRoleIds, isBoosting) {
      return request(`/internal/standard-giveaway-occurrences/${occurrenceId}/entries`, {
        method: 'POST',
        body: JSON.stringify({
          discord_user_id: discordUserId,
          discord_username: discordUsername,
          discord_role_ids: discordRoleIds,
          is_boosting: isBoosting,
        }),
      })
    },
  }
}
