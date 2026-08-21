import type { ForecastSummary } from '@/entities/forecast'

export function buildForecastSummary(overrides: Partial<ForecastSummary> = {}): ForecastSummary {
  return {
    month: '2026-06',
    periodStart: '2026-06-01',
    periodEnd: '2026-06-30',
    openDealCount: 1,
    pipelineTotalCents: 1_500_000,
    weightedTotalCents: 600_000,
    byStage: [
      {
        stageId: '01STAGELEAD0000000000000AA',
        slug: 'lead',
        dealCount: 1,
        totalCents: 1_500_000,
        weightedTotalCents: 600_000,
      },
    ],
    ...overrides,
  }
}
