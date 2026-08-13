import { describe, expect, it } from 'vitest'
import { joinResultReply } from '../src/joinResultReply.js'

describe('joinResultReply', () => {
  it('names the won item', () => {
    const text = joinResultReply({ status: 'won', item: { id: 1, name: 'Joystick' } })

    expect(text).toContain('Joystick')
    expect(text).toContain('won')
  })

  it('shows the existing prize on a duplicate join', () => {
    const text = joinResultReply({ status: 'already_entered', item: { id: 2, name: 'Cartridge' } })

    expect(text).toContain('Cartridge')
    expect(text).toContain('already joined')
  })

  it('explains that the giveaway ended, without naming any item', () => {
    const text = joinResultReply({ status: 'expired', item: null })

    expect(text).toMatch(/ended/i)
  })

  it('throws on an unrecognized status rather than silently returning nothing', () => {
    expect(() => joinResultReply({ status: 'mystery', item: null })).toThrow()
  })
})
