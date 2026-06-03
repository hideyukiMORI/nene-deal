import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { DealDto, InvoiceHandoffResultDto, StageChangeDto } from './api-types'
import type { DealId } from './ids'
import {
  mapCreateInputToDto,
  mapDealDtoToModel,
  mapHandoffResultDtoToModel,
  mapUpdateInputToDto,
} from './mapper'
import type { CreateDealInput, Deal, InvoiceHandoffResult, UpdateDealInput } from './model'
import { dealKeys } from './query-keys'

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

export interface UpdateDealVars {
  id: DealId
  input: UpdateDealInput
}

export function useUpdateDeal(): UseMutationResult<Deal, AppError, UpdateDealVars> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.patch<DealDto>(
        `/api/v1/deals/${encodeURIComponent(id)}`,
        mapUpdateInputToDto(input),
      )
      return mapDealDtoToModel(dto)
    },
    onSuccess: async (_data, variables) => {
      await queryClient.invalidateQueries({ queryKey: dealKeys.detail(variables.id) })
    },
  })
}

export function useDeleteDeal(): UseMutationResult<void, AppError, DealId> {
  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/deals/${encodeURIComponent(id)}`)
    },
  })
}

export function useRestoreDeal(): UseMutationResult<Deal, AppError, DealId> {
  return useMutation({
    mutationFn: async (id) => {
      const dto = await apiClient.post<DealDto>(
        `/api/v1/deals/${encodeURIComponent(id)}/restore`,
        {},
      )
      return mapDealDtoToModel(dto)
    },
  })
}

export function useInvoiceHandoff(): UseMutationResult<InvoiceHandoffResult, AppError, DealId> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id) => {
      const dto = await apiClient.post<InvoiceHandoffResultDto>(
        `/api/v1/deals/${encodeURIComponent(id)}/invoice-handoff`,
        {},
      )
      return mapHandoffResultDtoToModel(dto)
    },
    onSuccess: async (_data, id) => {
      await queryClient.invalidateQueries({ queryKey: dealKeys.detail(id) })
    },
  })
}
