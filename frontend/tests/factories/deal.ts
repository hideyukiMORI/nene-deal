import { toDealId, type Deal } from '@/entities/deal'

export function buildDeal(overrides: Partial<Deal> = {}): Deal {
  return {
    id: toDealId('01DEALACME000000000000000A'),
    accountLabel: 'Acme Corp',
    amountCents: 150_000_000,
    stageId: '01STAGEWON00000000000000AA',
    stageSlug: 'won',
    probabilityPercent: 100,
    expectedCloseDate: null,
    ownerLabel: null,
    note: null,
    invoiceClientId: null,
    invoiceQuoteId: null,
    handoffAt: null,
    deletedAt: null,
    ...overrides,
  }
}
