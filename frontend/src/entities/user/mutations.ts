import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { OperatorUserDto } from './api-types'
import { mapCreateUserInput, mapUpdateUserInput, mapUserDto } from './mapper'
import type { CreateUserInput, OperatorUser, UpdateUserInput } from './model'
import { userKeys } from './query-keys'

export function useCreateUser(): UseMutationResult<OperatorUser, AppError, CreateUserInput> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<OperatorUserDto>('/api/v1/users', mapCreateUserInput(input))
      return mapUserDto(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: userKeys.all })
    },
  })
}

export function useUpdateUser(): UseMutationResult<
  OperatorUser,
  AppError,
  { userId: string } & UpdateUserInput
> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ userId, ...input }) => {
      const dto = await apiClient.patch<OperatorUserDto>(
        `/api/v1/users/${userId}`,
        mapUpdateUserInput(input),
      )
      return mapUserDto(dto)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: userKeys.all })
    },
  })
}

export function useDeleteUser(): UseMutationResult<void, AppError, string> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (userId) => {
      await apiClient.delete(`/api/v1/users/${userId}`)
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: userKeys.all })
    },
  })
}
