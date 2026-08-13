import { describe, expect, it } from 'vitest'
import { buildStandardGiveawayOccurrenceMessage, STANDARD_GIVEAWAY_ENTER_PREFIX } from '../src/standardGiveawayOccurrenceMessage.js'

const payload = {
  occurrence_id: 7,
  title: 'Nitro Friday',
  description: 'Weekly booster giveaway.',
  ends_at: '2026-06-01T20:00:00Z',
  requires_booster: false,
  required_role_ids: [],
  prize_item_names: ['Golden Coin'],
}

describe('buildStandardGiveawayOccurrenceMessage', () => {
  it('encodes the occurrence id into the Enter button customId', () => {
    const message = buildStandardGiveawayOccurrenceMessage(payload)
    const button = message.components[0].components[0]

    expect(button.data.custom_id).toBe(`${STANDARD_GIVEAWAY_ENTER_PREFIX}7`)
  })

  it('includes the title, description, and prize item names in the embed', () => {
    const message = buildStandardGiveawayOccurrenceMessage(payload)
    const embedData = message.embeds[0].data

    expect(embedData.title).toContain('Nitro Friday')
    expect(embedData.description).toBe('Weekly booster giveaway.')
    expect(embedData.fields.find((f) => f.name === 'Prize(s)').value).toBe('Golden Coin')
  })

  it('omits the eligibility field when there are no restrictions', () => {
    const message = buildStandardGiveawayOccurrenceMessage(payload)
    const embedData = message.embeds[0].data

    expect(embedData.fields.find((f) => f.name === 'Eligibility')).toBeUndefined()
  })

  it('includes a booster-only note when requires_booster is true', () => {
    const message = buildStandardGiveawayOccurrenceMessage({ ...payload, requires_booster: true })
    const embedData = message.embeds[0].data

    expect(embedData.fields.find((f) => f.name === 'Eligibility').value).toContain('Server boosters only')
  })

  it('includes required role mentions when required_role_ids is non-empty', () => {
    const message = buildStandardGiveawayOccurrenceMessage({ ...payload, required_role_ids: ['111', '222'] })
    const embedData = message.embeds[0].data

    expect(embedData.fields.find((f) => f.name === 'Eligibility').value).toContain('<@&111>')
    expect(embedData.fields.find((f) => f.name === 'Eligibility').value).toContain('<@&222>')
  })
})
