import { useQueryClient } from '@tanstack/react-query'
import { useCallback } from 'react'
import { boardKeys } from '@/entities/board'
import {
  toDealId,
  useDeal,
  useInvoiceHandoff,
  useUpdateDeal,
  type Deal,
  type InvoiceHandoffResult,
  type UpdateDealInput,
} from '@/entities/deal'
import { forecastKeys } from '@/entities/forecast'
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
}

export function useDealDetailPage(dealId: string | undefined): DealDetailPage {
  const queryClient = useQueryClient()
  const id = toDealId(dealId ?? '')
  const dealQuery = useDeal(id)
  const updateMutation = useUpdateDeal()
  const handoffMutation = useInvoiceHandoff()

  const invalidatePipelineViews = useCallback(async () => {
    await queryClient.invalidateQueries({ queryKey: boardKeys.all })
    await queryClient.invalidateQueries({ queryKey: forecastKeys.all })
  }, [queryClient])

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

  const handoff = useCallback(async (): Promise<boolean> => {
    try {
      await handoffMutation.mutateAsync(id)
      await invalidatePipelineViews()
      return true
    } catch {
      return false
    }
  }, [handoffMutation, id, invalidatePipelineViews])

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
  }
}
