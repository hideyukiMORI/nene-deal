import { useQueryClient } from '@tanstack/react-query'
import { useCallback } from 'react'
import { boardKeys, useBoard, type KanbanColumn } from '@/entities/board'
import { toDealId, useChangeDealStage, useCreateDeal, type CreateDealInput } from '@/entities/deal'
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
  retry: () => void
  submitCreateDeal: (input: CreateDealInput) => Promise<boolean>
  createPending: boolean
  createErrorKey: MessageKey | null
  moveDeal: (dealId: string, toStageRef: string) => Promise<void>
}

function currentMonth(): string {
  const now = new Date()
  return `${String(now.getFullYear())}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

export function useKanbanBoardPage(): KanbanBoardPage {
  const queryClient = useQueryClient()
  const board = useBoard({ includeTerminal: false })
  const forecast = useForecast(currentMonth())
  const stages = useStageList()
  const createMutation = useCreateDeal()
  const changeMutation = useChangeDealStage()

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
      await changeMutation.mutateAsync({ id: toDealId(dealId), toStageRef })
      await invalidateBoardViews()
    },
    [changeMutation, invalidateBoardViews],
  )

  const status: BoardStatus = board.isPending ? 'loading' : board.isError ? 'error' : 'ready'

  return {
    status,
    errorMessageKey: board.error !== null ? mapProblemDetailsToMessageKey(board.error) : null,
    columns: board.data?.columns ?? [],
    forecast: forecast.data ?? null,
    stageOptions: (stages.data ?? []).map((stage) => ({ value: stage.slug, label: stage.label })),
    retry: () => void board.refetch(),
    submitCreateDeal,
    createPending: createMutation.isPending,
    createErrorKey:
      createMutation.error !== null ? mapProblemDetailsToMessageKey(createMutation.error) : null,
    moveDeal,
  }
}
