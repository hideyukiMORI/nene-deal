export interface PipelineStageDto {
  id: string
  organization_id: string
  slug: string
  label: string
  sort_order: number
  is_terminal: boolean
  is_won: boolean
}

export interface PipelineStageListDto {
  data: PipelineStageDto[]
}

export interface StageCreateDto {
  label: string
  sort_order: number
}

export interface StageUpdateDto {
  label?: string
  sort_order?: number
}
