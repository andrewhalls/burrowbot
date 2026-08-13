import { describe, expect, it } from 'vitest'
import { buildEventOccurrenceMessage, EVENT_NOT_ATTENDING_PREFIX, EVENT_ROLE_SELECT_PREFIX } from '../src/eventOccurrenceMessage.js'

const payload = {
  occurrence_id: 42,
  title: 'Raid Night',
  description: 'Bring your best gear.',
  scheduled_start_at: '2026-06-01T20:00:00Z',
  roles: [
    { id: 1, name: 'Tank' },
    { id: 2, name: 'Healer' },
  ],
}

describe('buildEventOccurrenceMessage', () => {
  it('encodes the occurrence id into the role-select customId', () => {
    const message = buildEventOccurrenceMessage(payload)
    const selectRow = message.components[0]
    const select = selectRow.components[0]

    expect(select.data.custom_id).toBe(`${EVENT_ROLE_SELECT_PREFIX}42`)
  })

  it('encodes the occurrence id into the Not Attending button customId', () => {
    const message = buildEventOccurrenceMessage(payload)
    const buttonRow = message.components[1]
    const button = buttonRow.components[0]

    expect(button.data.custom_id).toBe(`${EVENT_NOT_ATTENDING_PREFIX}42`)
  })

  it('creates one select option per role, using the role id as the value', () => {
    const message = buildEventOccurrenceMessage(payload)
    const select = message.components[0].components[0]
    const options = select.options.map((o) => o.data)

    expect(options).toEqual([
      expect.objectContaining({ label: 'Tank', value: '1' }),
      expect.objectContaining({ label: 'Healer', value: '2' }),
    ])
  })

  it('includes the title and description in the embed', () => {
    const message = buildEventOccurrenceMessage(payload)
    const embedData = message.embeds[0].data

    expect(embedData.title).toContain('Raid Night')
    expect(embedData.description).toBe('Bring your best gear.')
  })
})
