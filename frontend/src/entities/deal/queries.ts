import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { DealActivityListDto, DealDto } from './api-types'
import type { DealId } from './ids'
import { mapActivityDtoToModel, mapDealDtoToModel } from './mapper'
import type { Deal, DealActivity } from './model'
import { dealKeys } from './query-keys'

export function useDeal(id: DealId): UseQueryResult<Deal, AppError> {
  return useQuery({
    queryKey: dealKeys.detail(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<DealDto>(`/api/v1/deals/${encodeURIComponent(id)}`, signal)
      return mapDealDtoToModel(dto)
    },
    enabled: id.length > 0,
  })
}

export function useDealActivity(id: DealId): UseQueryResult<DealActivity[], AppError> {
  return useQuery({
    queryKey: dealKeys.activity(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<DealActivityListDto>(
        `/api/v1/deals/${encodeURIComponent(id)}/history`,
        signal,
      )
      return dto.data.map(mapActivityDtoToModel)
    },
    enabled: id.length > 0,
  })
}
