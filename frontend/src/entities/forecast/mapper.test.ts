import { describe, expect, it } from 'vitest'
import { mapForecastDtoToModel } from './mapper'

describe('forecast mapper', () => {
  it('maps the forecast summary and stage buckets', () => {
    const summary = mapForecastDtoToModel({
      month: '2026-06',
      period_start: '2026-06-01',
      period_end: '2026-06-30',
      open_deal_count: 2,
      pipeline_total_cents: 3_000_000,
      weighted_total_cents: 1_000_000,
      by_stage: [
        {
          stage_id: '01STAGELEAD0000000000000AA',
          slug: 'lead',
          deal_count: 1,
          total_cents: 1_000_000,
          weighted_total_cents: 500_000,
        },
      ],
    })

    expect(summary.month).toBe('2026-06')
    expect(summary.openDealCount).toBe(2)
    expect(summary.weightedTotalCents).toBe(1_000_000)
    expect(summary.byStage).toHaveLength(1)
    expect(summary.byStage[0]?.slug).toBe('lead')
  })
})
