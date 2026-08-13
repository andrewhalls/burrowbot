import { describe, expect, it } from 'vitest'
import { eventSignupResultReply } from '../src/eventSignupResultReply.js'

describe('eventSignupResultReply', () => {
  it('confirms the role by name', () => {
    const text = eventSignupResultReply({ status: 'confirmed', role: { id: 1, name: 'Tank' } })

    expect(text).toContain('Tank')
    expect(text).toContain('confirmed')
  })

  it('explains the waitlist for a full role', () => {
    const text = eventSignupResultReply({ status: 'waitlisted', role: { id: 2, name: 'Healer' } })

    expect(text).toContain('Healer')
    expect(text).toMatch(/waitlist/i)
  })

  it('uses the server-provided reason for a rejection', () => {
    const text = eventSignupResultReply({ status: 'rejected', role: null, reason: 'That role is full.' })

    expect(text).toBe('That role is full.')
  })

  it('falls back to a generic message when no reason is given', () => {
    const text = eventSignupResultReply({ status: 'rejected', role: null, reason: null })

    expect(text.length).toBeGreaterThan(0)
  })

  it('confirms not attending', () => {
    const text = eventSignupResultReply({ status: 'not_attending' })

    expect(text).toMatch(/not attending/i)
  })

  it('throws on an unrecognized status', () => {
    expect(() => eventSignupResultReply({ status: 'mystery' })).toThrow()
  })
})
