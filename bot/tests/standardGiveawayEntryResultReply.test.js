import { describe, expect, it } from 'vitest'
import { standardGiveawayEntryResultReply } from '../src/standardGiveawayEntryResultReply.js'

describe('standardGiveawayEntryResultReply', () => {
  it('confirms entry for an entered result', () => {
    expect(standardGiveawayEntryResultReply({ status: 'entered' })).toContain("You're entered")
  })

  it('notes an already-entered result', () => {
    expect(standardGiveawayEntryResultReply({ status: 'already_entered' })).toContain('already entered')
  })

  it('uses the server-provided reason for a rejected result', () => {
    expect(standardGiveawayEntryResultReply({ status: 'rejected', reason: 'Boosters only.' })).toBe('Boosters only.')
  })

  it('falls back to a generic message for a rejected result with no reason', () => {
    expect(standardGiveawayEntryResultReply({ status: 'rejected', reason: null })).toContain('not eligible')
  })

  it('notes a closed occurrence', () => {
    expect(standardGiveawayEntryResultReply({ status: 'closed' })).toContain('already ended')
  })

  it('throws on an unknown status', () => {
    expect(() => standardGiveawayEntryResultReply({ status: 'mystery' })).toThrow()
  })
})
