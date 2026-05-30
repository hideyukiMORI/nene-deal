import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { DealDto, StageChangeDto } from './api-types'
import type { DealId } from './ids'
import { mapCreateInputToDto, mapDealDtoToModel } from './mapper'
import type { CreateDealInput, Deal } from './model'

/**
 * Creates a deal. Cross-resource cache invalidation (board / forecast) is the
 * caller feature's responsibility — it composes these entity mutations.
 */
export function useCreateDeal(): UseMutationResult<Deal, AppError, CreateDealInput> {
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<DealDto>('/api/v1/deals', mapCreateInputToDto(input))
      return mapDealDtoToModel(dto)
    },
  })
}

export interface ChangeDealStageVars {
  id: DealId
  toStageRef: string
}

export function useChangeDealStage(): UseMutationResult<Deal, AppError, ChangeDealStageVars> {
  return useMutation({
    mutationFn: async ({ id, toStageRef }) => {
      const body: StageChangeDto = { to_stage_id: toStageRef }
      const dto = await apiClient.post<DealDto>(
        `/api/v1/deals/${encodeURIComponent(id)}/stage-change`,
        body,
      )
      return mapDealDtoToModel(dto)
    },
  })
}
