import type { KanbanColumn, KanbanDeal } from '@/entities/board'

export function buildKanbanDeal(overrides: Partial<KanbanDeal> = {}): KanbanDeal {
  return {
    id: '01DEALACME000000000000000A',
    accountLabel: 'Acme Corp',
    amountCents: 150_000_000,
    probabilityPercent: 40,
    ...overrides,
  }
}

export function buildKanbanColumn(overrides: Partial<KanbanColumn> = {}): KanbanColumn {
  const deals = overrides.deals ?? [buildKanbanDeal()]
  return {
    stageId: '01STAGELEAD0000000000000AA',
    stageSlug: 'lead',
    stageLabel: 'Lead',
    isWon: false,
    deals,
    dealCount: deals.length,
    totalCents: 150_000_000,
    weightedTotalCents: 60_000_000,
    ...overrides,
  }
}
