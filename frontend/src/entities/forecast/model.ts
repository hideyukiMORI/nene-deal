export interface ForecastBucket {
  stageId: string
  slug: string
  dealCount: number
  totalCents: number
  weightedTotalCents: number
}

export interface ForecastSummary {
  month: string
  openDealCount: number
  pipelineTotalCents: number
  weightedTotalCents: number
  byStage: ForecastBucket[]
}
