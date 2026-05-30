export interface BoardStageDto {
  id: string
  slug: string
  label: string
  is_won: boolean
}

export interface BoardDealDto {
  id: string
  account_label: string
  amount_cents: number
  stage_slug?: string | null
  probability_percent: number
}

export interface BoardColumnDto {
  stage: BoardStageDto
  deals: BoardDealDto[]
  deal_count: number
  total_cents: number
  weighted_total_cents: number
}

export interface KanbanBoardDto {
  columns: BoardColumnDto[]
}
