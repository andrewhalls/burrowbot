import { describe, expect, it } from 'vitest'
import { postableRoles } from '../src/discordRoles.js'

describe('postableRoles', () => {
  it('includes non-managed roles', () => {
    const roles = [
      { id: '1', name: 'Moderator', managed: false },
      { id: '2', name: 'Raider', managed: false },
    ]

    expect(postableRoles(roles, 'guild-1')).toEqual([
      { discord_role_id: '1', name: 'Moderator' },
      { discord_role_id: '2', name: 'Raider' },
    ])
  })

  it('excludes the @everyone role (id equal to the guild id)', () => {
    const roles = [
      { id: 'guild-1', name: '@everyone', managed: false },
      { id: '2', name: 'Moderator', managed: false },
    ]

    expect(postableRoles(roles, 'guild-1')).toEqual([{ discord_role_id: '2', name: 'Moderator' }])
  })

  it('excludes Discord-managed roles', () => {
    const roles = [
      { id: '1', name: 'MEE6', managed: true },
      { id: '2', name: 'Server Booster', managed: true },
      { id: '3', name: 'Moderator', managed: false },
    ]

    expect(postableRoles(roles, 'guild-1')).toEqual([{ discord_role_id: '3', name: 'Moderator' }])
  })

  it('returns an empty list for a guild with no roles', () => {
    expect(postableRoles([], 'guild-1')).toEqual([])
  })
})
