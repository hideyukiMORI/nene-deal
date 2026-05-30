import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { BoardPage } from '@/pages/board/BoardPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'

const router = createBrowserRouter([
  { path: '/', element: <BoardPage />, errorElement: <NotFoundPage /> },
  { path: '*', element: <NotFoundPage /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
