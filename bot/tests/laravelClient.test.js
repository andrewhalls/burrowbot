import { describe, expect, it, vi } from 'vitest'
import { createLaravelClient } from '../src/laravelClient.js'

function fakeFetch(responseBody, { ok = true, status = 200 } = {}) {
  return vi.fn().mockResolvedValue({
    ok,
    status,
    json: async () => responseBody,
    text: async () => JSON.stringify(responseBody),
  })
}

describe('createLaravelClient', () => {
  it('sends the bot service token as a bearer header on every request', async () => {
    const fetchImpl = fakeFetch({ id: 1 })
    const client = createLaravelClient({ baseUrl: 'http://laravel.test', serviceToken: 'secret-token', fetchImpl })

    await client.guildJoined('999', 'Acme Server')

    const [url, options] = fetchImpl.mock.calls[0]
    expect(url).toBe('http://laravel.test/internal/guilds')
    expect(options.headers.Authorization).toBe('Bearer secret-token')
    expect(JSON.parse(options.body)).toEqual({ discord_guild_id: '999', name: 'Acme Server' })
  })

  it('throws with a descriptive message on a non-2xx response', async () => {
    const fetchImpl = fakeFetch({ message: 'Unauthenticated.' }, { ok: false, status: 401 })
    const client = createLaravelClient({ baseUrl: 'http://laravel.test', serviceToken: 'bad', fetchImpl })

    await expect(client.listActiveGiveaways()).rejects.toThrow(/401/)
  })

  it('omits the since query param when not provided', async () => {
    const fetchImpl = fakeFetch([])
    const client = createLaravelClient({ baseUrl: 'http://laravel.test', serviceToken: 't', fetchImpl })

    await client.listOutboundActions()

    expect(fetchImpl.mock.calls[0][0]).toBe('http://laravel.test/internal/outbound-actions')
  })

  it('includes the since query param when provided', async () => {
    const fetchImpl = fakeFetch([])
    const client = createLaravelClient({ baseUrl: 'http://laravel.test', serviceToken: 't', fetchImpl })

    await client.listOutboundActions(42)

    expect(fetchImpl.mock.calls[0][0]).toBe('http://laravel.test/internal/outbound-actions?since=42')
  })
})
