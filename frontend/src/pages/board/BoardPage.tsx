import { useNavigate } from 'react-router-dom'
import { useCurrentUser } from '@/entities/auth'
import { KanbanBoardView, useKanbanBoardPage } from '@/features/kanban-board'

export function BoardPage() {
  const page = useKanbanBoardPage()
  const navigate = useNavigate()
  const currentUser = useCurrentUser()
  const isAdmin = currentUser.data?.role === 'admin'

  return (
    <KanbanBoardView
      {...page}
      isAdmin={isAdmin}
      onOpenDeal={(dealId) => {
        void navigate(`/deals/${dealId}`)
      }}
      onOpenUsers={() => {
        void navigate('/users')
      }}
      onOpenStages={() => {
        void navigate('/stages')
      }}
    />
  )
}
