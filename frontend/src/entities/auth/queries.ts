import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import { useAuthToken } from '@/shared/auth'

export interface CurrentUser {
  id: string
  email: string
  role: string
  organizationId: string
}

interface CurrentUserDto {
  id: string
  email: string
  role: string
  organization_id: string
}

export function useCurrentUser(): UseQueryResult<CurrentUser, AppError> {
  const token = useAuthToken()
  return useQuery({
    queryKey: ['auth', 'me'],
    queryFn: async () => {
      const dto = await apiClient.get<CurrentUserDto>('/api/v1/auth/me')
      return {
        id: dto.id,
        email: dto.email,
        role: dto.role,
        organizationId: dto.organization_id,
      }
    },
    enabled: token !== null,
  })
}
