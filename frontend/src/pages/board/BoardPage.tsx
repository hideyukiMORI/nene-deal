import { useNavigate } from 'react-router-dom'
import { KanbanBoardView, useKanbanBoardPage } from '@/features/kanban-board'

export function BoardPage() {
  const page = useKanbanBoardPage()
  const navigate = useNavigate()

  return (
    <KanbanBoardView
      {...page}
      onOpenDeal={(dealId) => {
        void navigate(`/deals/${dealId}`)
      }}
    />
  )
}
