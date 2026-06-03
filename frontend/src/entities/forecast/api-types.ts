export interface ForecastStageBucketDto {
  stage_id: string
  slug: string
  deal_count: number
  total_cents: number
  weighted_total_cents: number
}

export interface ForecastSummaryDto {
  month: string
  period_start: string
  period_end: string
  open_deal_count: number
  pipeline_total_cents: number
  weighted_total_cents: number
  by_stage: ForecastStageBucketDto[]
}
