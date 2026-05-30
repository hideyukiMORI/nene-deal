import { KanbanBoardView, useKanbanBoardPage } from '@/features/kanban-board'

export function BoardPage() {
  const page = useKanbanBoardPage()
  return <KanbanBoardView {...page} />
}
