import type { BoardColumnDto, KanbanBoardDto } from './api-types'
import type { KanbanBoard, KanbanColumn } from './model'

function mapColumn(dto: BoardColumnDto): KanbanColumn {
  return {
    stageId: dto.stage.id,
    stageSlug: dto.stage.slug,
    stageLabel: dto.stage.label,
    isWon: dto.stage.is_won,
    deals: dto.deals.map((deal) => ({
      id: deal.id,
      accountLabel: deal.account_label,
      amountCents: deal.amount_cents,
      probabilityPercent: deal.probability_percent,
      ownerLabel: deal.owner_label ?? null,
      deletedAt: deal.deleted_at ?? null,
    })),
    dealCount: dto.deal_count,
    totalCents: dto.total_cents,
    weightedTotalCents: dto.weighted_total_cents,
  }
}

export function mapBoardDtoToModel(dto: KanbanBoardDto): KanbanBoard {
  return { columns: dto.columns.map(mapColumn) }
}
