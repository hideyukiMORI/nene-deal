import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { DealDto } from './api-types'
import type { DealId } from './ids'
import { mapDealDtoToModel } from './mapper'
import type { Deal } from './model'
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
