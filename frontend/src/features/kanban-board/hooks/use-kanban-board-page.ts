import { useQueryClient } from '@tanstack/react-query'
import { useCallback, useState } from 'react'
import {
  boardKeys,
  useBoard,
  type KanbanBoard,
  type KanbanColumn,
  type KanbanDeal,
} from '@/entities/board'
import {
  toDealId,
  useChangeDealStage,
  useCreateDeal,
  useRestoreDeal,
  type CreateDealInput,
} from '@/entities/deal'
import { forecastKeys, useForecast, type ForecastSummary } from '@/entities/forecast'
import { useStageList } from '@/entities/pipeline-stage'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface StageOption {
  value: string
  label: string
}

export type BoardStatus = 'loading' | 'error' | 'ready'

export interface KanbanBoardPage {
  status: BoardStatus
  errorMessageKey: MessageKey | null
  columns: KanbanColumn[]
  forecast: ForecastSummary | null
  stageOptions: StageOption[]
  includeTerminal: boolean
  toggleTerminal: () => void
  includeDeleted: boolean
  toggleDeleted: () => void
  retry: () => void
  submitCreateDeal: (input: CreateDealInput) => Promise<boolean>
  createPending: boolean
  createErrorKey: MessageKey | null
  moveDeal: (dealId: string, toStageRef: string) => Promise<void>
  restoreDeal: (dealId: string) => Promise<void>
}

function withColumnTotals(
  stageDeals: KanbanDeal[],
): Pick<KanbanColumn, 'dealCount' | 'totalCents' | 'weightedTotalCents'> {
  let totalCents = 0
  let weightedTotalCents = 0
  for (const deal of stageDeals) {
    totalCents += deal.amountCents
    weightedTotalCents += Math.floor((deal.amountCents * deal.probabilityPercent) / 100)
  }
  return { dealCount: stageDeals.length, totalCents, weightedTotalCents }
}

/**
 * Move a deal between columns in a cached board snapshot so the card lands in
 * its target immediately (no flash back to the source while the server round
 * trip is in flight). If the target column is not visible (e.g. a terminal
 * stage while hidden), the card simply leaves the board, which is correct.
 */
function applyOptimisticMove(board: KanbanBoard, dealId: string, toStageSlug: string): KanbanBoard {
  const moving = board.columns.flatMap((column) => column.deals).find((deal) => deal.id === dealId)
  if (moving === undefined) return board

  const columns = board.columns.map((column) => {
    const withoutDeal = column.deals.filter((deal) => deal.id !== dealId)
    const nextDeals = column.stageSlug === toStageSlug ? [...withoutDeal, moving] : withoutDeal
    if (nextDeals.length === column.deals.length && column.stageSlug !== toStageSlug) {
      return column
    }
    return { ...column, deals: nextDeals, ...withColumnTotals(nextDeals) }
  })

  return { ...board, columns }
}

export function useKanbanBoardPage(): KanbanBoardPage {
  const queryClient = useQueryClient()
  const [includeTerminal, setIncludeTerminal] = useState(false)
  const [includeDeleted, setIncludeDeleted] = useState(false)
  const board = useBoard({ includeTerminal, includeDeleted })
  // No month → the server returns the current open period (closing-day aware).
  const forecast = useForecast()
  const stages = useStageList()
  const createMutation = useCreateDeal()
  const changeMutation = useChangeDealStage()
  const restoreMutation = useRestoreDeal()

  const invalidateBoardViews = useCallback(async () => {
    await queryClient.invalidateQueries({ queryKey: boardKeys.all })
    await queryClient.invalidateQueries({ queryKey: forecastKeys.all })
  }, [queryClient])

  const submitCreateDeal = useCallback(
    async (input: CreateDealInput): Promise<boolean> => {
      try {
        await createMutation.mutateAsync(input)
        await invalidateBoardViews()
        return true
      } catch {
        // Error surfaces via createErrorKey; the form stays open.
        return false
      }
    },
    [createMutation, invalidateBoardViews],
  )

  const moveDeal = useCallback(
    async (dealId: string, toStageRef: string): Promise<void> => {
      const key = boardKeys.view({ includeTerminal, includeDeleted })
      const previous = queryClient.getQueryData<KanbanBoard>(key)
      // Optimistically reflect the move so the card lands in its target column
      // straight away instead of flashing back to the source during the request.
      if (previous !== undefined) {
        queryClient.setQueryData<KanbanBoard>(
          key,
          applyOptimisticMove(previous, dealId, toStageRef),
        )
      }
      try {
        await changeMutation.mutateAsync({ id: toDealId(dealId), toStageRef })
        await invalidateBoardViews()
      } catch (error) {
        if (previous !== undefined) queryClient.setQueryData<KanbanBoard>(key, previous)
        throw error
      }
    },
    [changeMutation, invalidateBoardViews, includeTerminal, includeDeleted, queryClient],
  )

  const restoreDeal = useCallback(
    async (dealId: string): Promise<void> => {
      await restoreMutation.mutateAsync(toDealId(dealId))
      await invalidateBoardViews()
    },
    [restoreMutation, invalidateBoardViews],
  )

  const status: BoardStatus = board.isPending ? 'loading' : board.isError ? 'error' : 'ready'

  return {
    status,
    errorMessageKey: board.error !== null ? mapProblemDetailsToMessageKey(board.error) : null,
    columns: board.data?.columns ?? [],
    forecast: forecast.data ?? null,
    stageOptions: (stages.data ?? []).map((stage) => ({ value: stage.slug, label: stage.label })),
    includeTerminal,
    toggleTerminal: () => {
      setIncludeTerminal((on) => !on)
    },
    includeDeleted,
    toggleDeleted: () => {
      setIncludeDeleted((on) => !on)
    },
    retry: () => void board.refetch(),
    submitCreateDeal,
    createPending: createMutation.isPending,
    createErrorKey:
      createMutation.error !== null ? mapProblemDetailsToMessageKey(createMutation.error) : null,
    moveDeal,
    restoreDeal,
  }
}
