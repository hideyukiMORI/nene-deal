import type { PipelineStageDto, PipelineStageListDto } from './api-types'
import { toStageId } from './ids'
import type { PipelineStage } from './model'

export function mapStageDtoToModel(dto: PipelineStageDto): PipelineStage {
  return {
    id: toStageId(dto.id),
    slug: dto.slug,
    label: dto.label,
    sortOrder: dto.sort_order,
    isTerminal: dto.is_terminal,
    isWon: dto.is_won,
  }
}

export function mapStageListDtoToModel(dto: PipelineStageListDto): PipelineStage[] {
  return dto.data.map(mapStageDtoToModel)
}
