import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { KanbanBoardDto } from './api-types'
import { mapBoardDtoToModel } from './mapper'
import type { KanbanBoard } from './model'
import { boardKeys } from './query-keys'

export interface BoardParams {
  includeTerminal: boolean
}

export function useBoard(params: BoardParams): UseQueryResult<KanbanBoard, AppError> {
  return useQuery({
    queryKey: boardKeys.view(params),
    queryFn: async ({ signal }) => {
      const search = new URLSearchParams()
      if (params.includeTerminal) {
        search.set('include_terminal', 'true')
      }
      const query = search.toString()
      const dto = await apiClient.get<KanbanBoardDto>(
        `/api/v1/board${query.length > 0 ? `?${query}` : ''}`,
        signal,
      )
      return mapBoardDtoToModel(dto)
    },
  })
}
