import { useQueryClient } from '@tanstack/react-query'
import { useCallback } from 'react'
import { boardKeys } from '@/entities/board'
import {
  dealKeys,
  toDealId,
  useChangeDealStage,
  useDeal,
  useDealActivity,
  useDeleteDeal,
  useInvoiceHandoff,
  useUpdateDeal,
  type Deal,
  type DealActivity,
  type InvoiceHandoffResult,
  type UpdateDealInput,
} from '@/entities/deal'
import { forecastKeys } from '@/entities/forecast'
import { useStageList, type PipelineStage } from '@/entities/pipeline-stage'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export type DealDetailStatus = 'loading' | 'error' | 'missing' | 'ready'

export interface DealDetailPage {
  status: DealDetailStatus
  errorMessageKey: MessageKey | null
  deal: Deal | null
  submitEdit: (input: UpdateDealInput) => Promise<boolean>
  editPending: boolean
  editErrorKey: MessageKey | null
  handoff: () => Promise<boolean>
  handoffPending: boolean
  handoffErrorKey: MessageKey | null
  handoffResult: InvoiceHandoffResult | null
  stages: PipelineStage[]
  changeStage: (toStageId: string) => Promise<boolean>
  stagePending: boolean
  deleteDeal: () => Promise<boolean>
  deletePending: boolean
  activity: DealActivity[]
}

export function useDealDetailPage(dealId: string | undefined): DealDetailPage {
  const queryClient = useQueryClient()
  const id = toDealId(dealId ?? '')
  const dealQuery = useDeal(id)
  const stagesQuery = useStageList()
  const updateMutation = useUpdateDeal()
  const handoffMutation = useInvoiceHandoff()
  const changeStageMutation = useChangeDealStage()
  const deleteMutation = useDeleteDeal()
  const activityQuery = useDealActivity(id)

  const invalidatePipelineViews = useCallback(async () => {
    await queryClient.invalidateQueries({ queryKey: boardKeys.all })
    await queryClient.invalidateQueries({ queryKey: forecastKeys.all })
    // The detail and its audit trail change with every write.
    await queryClient.invalidateQueries({ queryKey: dealKeys.detail(id) })
    await queryClient.invalidateQueries({ queryKey: dealKeys.activity(id) })
  }, [queryClient, id])

  const submitEdit = useCallback(
    async (input: UpdateDealInput): Promise<boolean> => {
      try {
        await updateMutation.mutateAsync({ id, input })
        await invalidatePipelineViews()
        return true
      } catch {
        return false
      }
    },
    [updateMutation, id, invalidatePipelineViews],
  )

  const changeStage = useCallback(
    async (toStageId: string): Promise<boolean> => {
      try {
        await changeStageMutation.mutateAsync({ id, toStageRef: toStageId })
        await invalidatePipelineViews()
        return true
      } catch {
        return false
      }
    },
    [changeStageMutation, id, invalidatePipelineViews],
  )

  const handoff = useCallback(async (): Promise<boolean> => {
    try {
      await handoffMutation.mutateAsync(id)
      await invalidatePipelineViews()
      return true
    } catch {
      return false
    }
  }, [handoffMutation, id, invalidatePipelineViews])

  const deleteDeal = useCallback(async (): Promise<boolean> => {
    try {
      await deleteMutation.mutateAsync(id)
      // The deleted deal disappears from the list views — refresh those.
      await queryClient.invalidateQueries({ queryKey: boardKeys.all })
      await queryClient.invalidateQueries({ queryKey: forecastKeys.all })
      // Deliberately leave this deal's own detail/activity queries untouched.
      // The detail page is still mounted here, so invalidating them (as the
      // shared invalidatePipelineViews does) would immediately refetch a
      // resource that no longer exists and surface a 404 in the console
      // (issue #84); evicting them refetches too, because the observer is
      // still active. The page navigates away right after delete, which
      // unmounts the observers and lets React Query garbage-collect the cache.
      return true
    } catch {
      return false
    }
  }, [deleteMutation, id, queryClient])

  let status: DealDetailStatus
  if (dealId === undefined || dealId === '') {
    status = 'missing'
  } else if (dealQuery.isPending) {
    status = 'loading'
  } else if (dealQuery.isError) {
    status = dealQuery.error.status === 404 ? 'missing' : 'error'
  } else {
    status = 'ready'
  }

  return {
    status,
    errorMessageKey:
      dealQuery.error !== null ? mapProblemDetailsToMessageKey(dealQuery.error) : null,
    deal: dealQuery.data ?? null,
    submitEdit,
    editPending: updateMutation.isPending,
    editErrorKey:
      updateMutation.error !== null ? mapProblemDetailsToMessageKey(updateMutation.error) : null,
    handoff,
    handoffPending: handoffMutation.isPending,
    handoffErrorKey:
      handoffMutation.error !== null ? mapProblemDetailsToMessageKey(handoffMutation.error) : null,
    handoffResult: handoffMutation.data ?? null,
    stages: stagesQuery.data ?? [],
    changeStage,
    stagePending: changeStageMutation.isPending,
    deleteDeal,
    deletePending: deleteMutation.isPending,
    activity: activityQuery.data ?? [],
  }
}
