import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { ForecastSummaryDto } from './api-types'
import { mapForecastDtoToModel } from './mapper'
import type { ForecastSummary } from './model'
import { forecastKeys } from './query-keys'

/**
 * Forecast for a period. Pass a `YYYY-MM` month, or omit it to get the current
 * open period (the server resolves it from the org's forecast closing day).
 */
export function useForecast(month?: string): UseQueryResult<ForecastSummary, AppError> {
  const hasMonth = month !== undefined && month !== ''
  return useQuery({
    queryKey: hasMonth ? forecastKeys.month(month) : forecastKeys.current(),
    queryFn: async ({ signal }) => {
      const path = hasMonth
        ? `/api/v1/forecast?month=${encodeURIComponent(month)}`
        : '/api/v1/forecast'
      const dto = await apiClient.get<ForecastSummaryDto>(path, signal)
      return mapForecastDtoToModel(dto)
    },
  })
}
