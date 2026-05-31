import { useCurrentUser } from '@/entities/auth'
import { useUserManagementPage, UserManagementView } from '@/features/user-management'

export function UsersPage() {
  const currentUser = useCurrentUser()
  const page = useUserManagementPage()

  return <UserManagementView {...page} currentUserId={currentUser.data?.id ?? null} />
}
