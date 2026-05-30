import type { StageId } from './ids'

export interface PipelineStage {
  id: StageId
  slug: string
  label: string
  sortOrder: number
  isTerminal: boolean
  isWon: boolean
}
