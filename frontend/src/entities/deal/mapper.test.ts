import { describe, expect, it } from 'vitest'
import { mapCreateInputToDto, mapDealDtoToModel } from './mapper'
import { toDealId } from './ids'

describe('deal mapper', () => {
  it('maps a deal dto to model', () => {
    const model = mapDealDtoToModel({
      id: '01DEAL0000000000000000000A',
      organization_id: '01ORG0000000000000000000AA',
      account_label: 'Acme Corp',
      amount_cents: 150_000_000,
      stage_id: '01STAGELEAD0000000000000AA',
      stage_slug: 'lead',
      probability_percent: 40,
      created_at: '2026-05-30 00:00:00',
      updated_at: '2026-05-30 00:00:00',
    })

    expect(model).toEqual({
      id: toDealId('01DEAL0000000000000000000A'),
      accountLabel: 'Acme Corp',
      amountCents: 150_000_000,
      stageId: '01STAGELEAD0000000000000AA',
      stageSlug: 'lead',
      probabilityPercent: 40,
    })
  })

  it('maps create input to dto', () => {
    expect(
      mapCreateInputToDto({
        accountLabel: 'Globex',
        amountCents: 2000,
        stageRef: 'lead',
        probabilityPercent: 25,
      }),
    ).toEqual({
      account_label: 'Globex',
      amount_cents: 2000,
      stage_id: 'lead',
      probability_percent: 25,
    })
  })
})
