import type { ForecastSummaryDto } from './api-types'
import type { ForecastSummary } from './model'

export function mapForecastDtoToModel(dto: ForecastSummaryDto): ForecastSummary {
  return {
    month: dto.month,
    periodStart: dto.period_start,
    periodEnd: dto.period_end,
    openDealCount: dto.open_deal_count,
    pipelineTotalCents: dto.pipeline_total_cents,
    weightedTotalCents: dto.weighted_total_cents,
    byStage: dto.by_stage.map((bucket) => ({
      stageId: bucket.stage_id,
      slug: bucket.slug,
      dealCount: bucket.deal_count,
      totalCents: bucket.total_cents,
      weightedTotalCents: bucket.weighted_total_cents,
    })),
  }
}
