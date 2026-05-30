import { describe, expect, it } from 'vitest'
import { mapStageDtoToModel, mapStageListDtoToModel } from './mapper'
import { toStageId } from './ids'

describe('pipeline-stage mapper', () => {
  it('maps a stage dto to model', () => {
    const model = mapStageDtoToModel({
      id: '01STAGEWON00000000000000AA',
      organization_id: '01ORG0000000000000000000AA',
      slug: 'won',
      label: 'Won',
      sort_order: 5,
      is_terminal: true,
      is_won: true,
    })

    expect(model).toEqual({
      id: toStageId('01STAGEWON00000000000000AA'),
      slug: 'won',
      label: 'Won',
      sortOrder: 5,
      isTerminal: true,
      isWon: true,
    })
  })

  it('maps a stage list dto to model array', () => {
    const list = mapStageListDtoToModel({
      data: [
        {
          id: '01STAGELEAD0000000000000AA',
          organization_id: '01ORG0000000000000000000AA',
          slug: 'lead',
          label: 'Lead',
          sort_order: 1,
          is_terminal: false,
          is_won: false,
        },
      ],
    })

    expect(list).toHaveLength(1)
    expect(list[0]?.slug).toBe('lead')
    expect(list[0]?.isTerminal).toBe(false)
  })
})
