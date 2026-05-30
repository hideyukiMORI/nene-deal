import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { LoginResultDto } from './api-types'
import { mapLoginInputToDto } from './mapper'
import type { LoginInput } from './model'

/**
 * Exchanges credentials for a bearer token. Storing the token is the caller
 * feature's responsibility (it owns the session side effect).
 */
export function useLogin(): UseMutationResult<string, AppError, LoginInput> {
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<LoginResultDto>(
        '/api/v1/auth/login',
        mapLoginInputToDto(input),
      )
      return dto.token
    },
  })
}
