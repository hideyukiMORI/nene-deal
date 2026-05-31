import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OperatorUserDto } from './api-types'
import { mapUserDto } from './mapper'
import type { OperatorUser } from './model'
import { userKeys } from './query-keys'

export function useUsers(): UseQueryResult<OperatorUser[], AppError> {
  return useQuery({
    queryKey: userKeys.all,
    queryFn: async () => {
      const result = await apiClient.get<{ data: OperatorUserDto[] }>('/api/v1/users')
      return result.data.map(mapUserDto)
    },
  })
}
