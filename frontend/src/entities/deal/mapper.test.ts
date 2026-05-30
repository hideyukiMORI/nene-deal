import { describe, expect, it } from 'vitest'
import { mapCreateInputToDto, mapDealDtoToModel, mapHandoffResultDtoToModel } from './mapper'
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
      note: 'first deal',
      invoice_client_id: null,
      invoice_quote_id: null,
      handoff_at: null,
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
      expectedCloseDate: null,
      note: 'first deal',
      invoiceClientId: null,
      invoiceQuoteId: null,
      handoffAt: null,
    })
  })

  it('maps a handoff result dto to model', () => {
    expect(
      mapHandoffResultDtoToModel({
        deal_id: '01DEAL0000000000000000000A',
        invoice_client_id: 4821,
        invoice_quote_id: 9930,
        handoff_at: '2026-05-31 00:00:00',
      }),
    ).toEqual({
      dealId: '01DEAL0000000000000000000A',
      invoiceClientId: 4821,
      invoiceQuoteId: 9930,
      handoffAt: '2026-05-31 00:00:00',
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
