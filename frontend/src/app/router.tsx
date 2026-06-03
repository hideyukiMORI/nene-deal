import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { BoardPage } from '@/pages/board/BoardPage'
import { DealDetailPage } from '@/pages/deal-detail/DealDetailPage'
import { LoginPage } from '@/pages/login/LoginPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'
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
    ],
  },
  { path: '*', element: <NotFoundPage /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
