import {
  useCreateStage,
  useDeleteStage,
  useStageList,
  useUpdateStage,
  type CreateStageInput,
  type PipelineStage,
  type UpdateStageInput,
} from '@/entities/pipeline-stage'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export type StageManagementStatus = 'loading' | 'error' | 'ready'

export interface StageManagementPage {
  status: StageManagementStatus
  errorKey: MessageKey | null
  stages: PipelineStage[]
  retry: () => void
  createStage: (input: CreateStageInput) => Promise<boolean>
  createPending: boolean
  createErrorKey: MessageKey | null
  updateStage: (stageId: string, input: UpdateStageInput) => Promise<boolean>
  deleteStage: (stageId: string) => Promise<boolean>
}

export function useStageManagementPage(): StageManagementPage {
  const stagesQuery = useStageList()
  const createMutation = useCreateStage()
  const updateMutation = useUpdateStage()
  const deleteMutation = useDeleteStage()

  const status: StageManagementStatus = stagesQuery.isPending
    ? 'loading'
    : stagesQuery.isError
      ? 'error'
      : 'ready'

  return {
    status,
    errorKey: stagesQuery.error !== null ? mapProblemDetailsToMessageKey(stagesQuery.error) : null,
    stages: stagesQuery.data ?? [],
    retry: () => void stagesQuery.refetch(),

    createStage: async (input) => {
      try {
        await createMutation.mutateAsync(input)
        return true
      } catch {
        return false
      }
    },
    createPending: createMutation.isPending,
    createErrorKey:
      createMutation.error !== null ? mapProblemDetailsToMessageKey(createMutation.error) : null,

    updateStage: async (stageId, input) => {
      try {
        await updateMutation.mutateAsync({ stageId, ...input })
        return true
      } catch {
        return false
      }
    },

    deleteStage: async (stageId) => {
      try {
        await deleteMutation.mutateAsync(stageId)
        return true
      } catch {
        return false
      }
    },
  }
}
