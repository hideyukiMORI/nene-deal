import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { SettingsDto } from './api-types'
import type { OrgSettings } from './model'
import { settingsKeys } from './query-keys'

export function useSettings(): UseQueryResult<OrgSettings, AppError> {
  return useQuery({
    queryKey: settingsKeys.all,
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<SettingsDto>('/api/v1/settings', signal)
      return { forecastClosingDay: dto.forecast_closing_day }
    },
  })
}
