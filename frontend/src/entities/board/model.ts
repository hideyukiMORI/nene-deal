export interface KanbanDeal {
  id: string
  accountLabel: string
  amountCents: number
  probabilityPercent: number
  ownerLabel: string | null
  deletedAt: string | null
}

export interface KanbanColumn {
  stageId: string
  stageSlug: string
  stageLabel: string
  isWon: boolean
  deals: KanbanDeal[]
  dealCount: number
  totalCents: number
  weightedTotalCents: number
}

export interface KanbanBoard {
  columns: KanbanColumn[]
}
