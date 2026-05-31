import { useCreateUser, useDeleteUser, useUpdateUser, useUsers } from '@/entities/user'
import type { CreateUserInput, OperatorRole, OperatorUser } from '@/entities/user'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export type UserManagementStatus = 'loading' | 'error' | 'ready'

export interface UserManagementPage {
  status: UserManagementStatus
  errorKey: MessageKey | null
  users: OperatorUser[]
  createUser: (input: CreateUserInput) => Promise<boolean>
  createPending: boolean
  createErrorKey: MessageKey | null
  updateRole: (userId: string, role: OperatorRole) => Promise<boolean>
  deleteUser: (userId: string) => Promise<boolean>
  retry: () => void
}

export function useUserManagementPage(): UserManagementPage {
  const usersQuery = useUsers()
  const createMutation = useCreateUser()
  const updateMutation = useUpdateUser()
  const deleteMutation = useDeleteUser()

  const status: UserManagementStatus = usersQuery.isPending
    ? 'loading'
    : usersQuery.isError
      ? 'error'
      : 'ready'

  return {
    status,
    errorKey: usersQuery.error !== null ? mapProblemDetailsToMessageKey(usersQuery.error) : null,
    users: usersQuery.data ?? [],
    retry: () => void usersQuery.refetch(),

    createUser: async (input: CreateUserInput): Promise<boolean> => {
      try {
        await createMutation.mutateAsync(input)
        return true
      } catch {
        return false
      }
    },
    createPending: createMutation.isPending,
    createErrorKey:
      createMutation.error !== null ? mapProblemDetailsToMessageKey(createMutation.error) : null,

    updateRole: async (userId: string, role: OperatorRole): Promise<boolean> => {
      try {
        await updateMutation.mutateAsync({ userId, role })
        return true
      } catch {
        return false
      }
    },

    deleteUser: async (userId: string): Promise<boolean> => {
      try {
        await deleteMutation.mutateAsync(userId)
        return true
      } catch {
        return false
      }
    },
  }
}
