import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { AuditPage } from '@/pages/audit/AuditPage'
import { BoardPage } from '@/pages/board/BoardPage'
import { DealDetailPage } from '@/pages/deal-detail/DealDetailPage'
import { LoginPage } from '@/pages/login/LoginPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'
import { SettingsPage } from '@/pages/settings/SettingsPage'
import { StagesPage } from '@/pages/stages/StagesPage'
import { UsersPage } from '@/pages/users/UsersPage'
import { AppShell } from './shell/AppShell'
import { RequireAdmin, RequireAuth } from './auth-gate'

const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  {
    element: (
      <RequireAuth>
        <AppShell />
      </RequireAuth>
    ),
    errorElement: <NotFoundPage />,
    children: [
      { path: '/', element: <BoardPage /> },
      { path: '/deals/:dealId', element: <DealDetailPage /> },
      {
        path: '/users',
        element: (
          <RequireAdmin>
            <UsersPage />
          </RequireAdmin>
        ),
      },
      {
        path: '/stages',
        element: (
          <RequireAdmin>
            <StagesPage />
          </RequireAdmin>
        ),
      },
      {
        path: '/audit',
        element: (
          <RequireAdmin>
            <AuditPage />
          </RequireAdmin>
        ),
      },
      {
        path: '/settings',
        element: (
          <RequireAdmin>
            <SettingsPage />
          </RequireAdmin>
        ),
      },
    ],
  },
  { path: '*', element: <NotFoundPage /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
