import { describe, expect, it } from 'vitest'
import { mapBoardDtoToModel } from './mapper'

describe('board mapper', () => {
  it('maps board columns and deals to camelCase model', () => {
    const board = mapBoardDtoToModel({
      columns: [
        {
          stage: { id: '01STAGELEAD0000000000000AA', slug: 'lead', label: 'Lead', is_won: false },
          deals: [
            {
              id: '01DEAL0000000000000000000A',
              account_label: 'Acme',
              amount_cents: 100_000_000,
              stage_slug: 'lead',
              probability_percent: 50,
            },
          ],
          deal_count: 1,
          total_cents: 100_000_000,
          weighted_total_cents: 50_000_000,
        },
      ],
    })

    expect(board.columns).toHaveLength(1)
    const column = board.columns[0]
    expect(column?.stageSlug).toBe('lead')
    expect(column?.isWon).toBe(false)
    expect(column?.dealCount).toBe(1)
    expect(column?.weightedTotalCents).toBe(50_000_000)
    expect(column?.deals[0]?.accountLabel).toBe('Acme')
  })
})
