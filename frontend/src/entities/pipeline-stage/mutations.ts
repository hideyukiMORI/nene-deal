import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { PipelineStageDto } from './api-types'
import type { PipelineStage } from './model'
import { mapStageDtoToModel } from './mapper'
import { stageKeys } from './query-keys'

export interface CreateStageInput {
  label: string
  sortOrder: number
}

export interface UpdateStageInput {
  label?: string
  sortOrder?: number
}

export function useCreateStage(): UseMutationResult<PipelineStage, AppError, CreateStageInput> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<PipelineStageDto>('/api/v1/stages', {
        label: input.label,
        sort_order: input.sortOrder,
      })
      return mapStageDtoToModel(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: stageKeys.all })
    },
  })
}

export function useUpdateStage(): UseMutationResult<
  PipelineStage,
  AppError,
  { stageId: string } & UpdateStageInput
> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ stageId, ...input }) => {
      const body: Record<string, unknown> = {}
      if (input.label !== undefined) body.label = input.label
      if (input.sortOrder !== undefined) body.sort_order = input.sortOrder
      const dto = await apiClient.patch<PipelineStageDto>(`/api/v1/stages/${stageId}`, body)
      return mapStageDtoToModel(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: stageKeys.all })
    },
  })
}

export function useDeleteStage(): UseMutationResult<void, AppError, string> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (stageId) => {
      await apiClient.delete(`/api/v1/stages/${stageId}`)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: stageKeys.all })
    },
  })
}
