import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import { forecastKeys } from '@/entities/forecast'
import type { SettingsDto } from './api-types'
import type { OrgSettings } from './model'
import { settingsKeys } from './query-keys'

export function useUpdateSettings(): UseMutationResult<OrgSettings, AppError, number | null> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (forecastClosingDay) => {
      const dto = await apiClient.patch<SettingsDto>('/api/v1/settings', {
        forecast_closing_day: forecastClosingDay,
      })
      return { forecastClosingDay: dto.forecast_closing_day }
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: settingsKeys.all })
      // The forecast period depends on the closing day.
      await queryClient.invalidateQueries({ queryKey: forecastKeys.all })
    },
  })
}
