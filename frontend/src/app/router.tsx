import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { BoardPage } from '@/pages/board/BoardPage'
import { DealDetailPage } from '@/pages/deal-detail/DealDetailPage'
import { LoginPage } from '@/pages/login/LoginPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'
import { UsersPage } from '@/pages/users/UsersPage'
import { RequireAdmin, RequireAuth } from './auth-gate'

const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  {
    path: '/',
    element: (
      <RequireAuth>
        <BoardPage />
      </RequireAuth>
    ),
    errorElement: <NotFoundPage />,
  },
  {
    path: '/deals/:dealId',
    element: (
      <RequireAuth>
        <DealDetailPage />
      </RequireAuth>
    ),
    errorElement: <NotFoundPage />,
  },
  {
    path: '/users',
    element: (
      <RequireAuth>
        <RequireAdmin>
          <UsersPage />
        </RequireAdmin>
      </RequireAuth>
    ),
    errorElement: <NotFoundPage />,
  },
  { path: '*', element: <NotFoundPage /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
