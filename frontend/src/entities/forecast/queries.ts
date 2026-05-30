import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { ForecastSummaryDto } from './api-types'
import { mapForecastDtoToModel } from './mapper'
import type { ForecastSummary } from './model'
import { forecastKeys } from './query-keys'

export function useForecast(month: string): UseQueryResult<ForecastSummary, AppError> {
  return useQuery({
    queryKey: forecastKeys.month(month),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<ForecastSummaryDto>(
        `/api/v1/forecast?month=${encodeURIComponent(month)}`,
        signal,
      )
      return mapForecastDtoToModel(dto)
    },
    enabled: month.length > 0,
  })
}
