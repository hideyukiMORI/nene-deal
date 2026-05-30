import type { ReactElement } from 'react'
import { Navigate } from 'react-router-dom'
import { env } from '@/shared/config/env'
import { useAuthToken } from '@/shared/auth'

/**
 * Fail-closed route guard. When `VITE_REQUIRE_LOGIN` is enabled and no bearer
 * token is present, redirects to `/login`. When login is not required (dev),
 * renders children unchanged so the open API stays usable.
 */
export function RequireAuth({ children }: { children: ReactElement }): ReactElement {
  const token = useAuthToken()

  if (env.requireLogin && token === null) {
    return <Navigate to="/login" replace />
  }

  return children
}
