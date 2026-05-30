import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { PipelineStageListDto } from './api-types'
import { mapStageListDtoToModel } from './mapper'
import type { PipelineStage } from './model'
import { stageKeys } from './query-keys'

export function useStageList(): UseQueryResult<PipelineStage[], AppError> {
  return useQuery({
    queryKey: stageKeys.list(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<PipelineStageListDto>('/api/v1/stages', signal)
      return mapStageListDtoToModel(dto)
    },
    // Stages rarely change; keep them warm to populate selects without refetching.
    staleTime: 5 * 60 * 1000,
  })
}
