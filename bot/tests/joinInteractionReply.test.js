import { describe, expect, it } from 'vitest'
import { buildJoinInteractionReplyOptions } from '../src/joinInteractionReply.js'

describe('buildJoinInteractionReplyOptions', () => {
  it('replies publicly (non-ephemeral) with an embed and image when a won item has one', () => {
    const result = { status: 'won', item: { id: 1, name: 'Joystick', image_url: 'https://example.test/joystick.png' } }

    const options = buildJoinInteractionReplyOptions(result, '111')

    expect(options.ephemeral).toBe(false)
    expect(options.embeds[0].data.description).toContain('<@111>')
    expect(options.embeds[0].data.description).toContain('Joystick')
    expect(options.embeds[0].data.image.url).toBe('https://example.test/joystick.png')
  })

  it('replies publicly with plain content (no embed) when a won item has no image', () => {
    const result = { status: 'won', item: { id: 1, name: 'Joystick', image_url: null } }

    const options = buildJoinInteractionReplyOptions(result, '111')

    expect(options.ephemeral).toBe(false)
    expect(options.embeds).toBeUndefined()
    expect(options.content).toContain('<@111>')
    expect(options.content).toContain('Joystick')
  })

  it('replies ephemerally for an already_entered result, unchanged from today', () => {
    const result = { status: 'already_entered', item: { id: 2, name: 'Cartridge', image_url: 'https://example.test/cartridge.png' } }

    const options = buildJoinInteractionReplyOptions(result, '111')

    expect(options.ephemeral).toBe(true)
    expect(options.embeds).toBeUndefined()
    expect(options.content).not.toContain('<@111>')
    expect(options.content).toContain('Cartridge')
  })

  it('replies ephemerally for an expired result, unchanged from today', () => {
    const result = { status: 'expired', item: null }

    const options = buildJoinInteractionReplyOptions(result, '111')

    expect(options.ephemeral).toBe(true)
    expect(options.embeds).toBeUndefined()
    expect(options.content).toMatch(/ended/i)
  })
})
