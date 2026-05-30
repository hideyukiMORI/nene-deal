import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { BoardPage } from '@/pages/board/BoardPage'
import { DealDetailPage } from '@/pages/deal-detail/DealDetailPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'

const router = createBrowserRouter([
  { path: '/', element: <BoardPage />, errorElement: <NotFoundPage /> },
  { path: '/deals/:dealId', element: <DealDetailPage />, errorElement: <NotFoundPage /> },
  { path: '*', element: <NotFoundPage /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
