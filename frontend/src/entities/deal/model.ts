import type { DealId } from './ids'

export interface Deal {
  id: DealId
  accountLabel: string
  amountCents: number
  stageId: string
  stageSlug: string | null
  probabilityPercent: number
  expectedCloseDate: string | null
  ownerLabel: string | null
  note: string | null
  invoiceClientId: number | null
  invoiceQuoteId: number | null
  handoffAt: string | null
  deletedAt: string | null
}

export interface CreateDealInput {
  accountLabel: string
  amountCents: number
  /** Target stage as slug or ULID. */
  stageRef: string
  probabilityPercent: number
  /** `YYYY-MM-DD`, or null when no close date is set. */
  expectedCloseDate: string | null
}

export interface UpdateDealInput {
  accountLabel: string
  amountCents: number
  probabilityPercent: number
  note: string | null
  /** `YYYY-MM-DD`, or null to clear the close date. */
  expectedCloseDate: string | null
}

export type DealActivityAction =
  | 'created'
  | 'updated'
  | 'stage_changed'
  | 'deleted'
  | 'restored'
  | 'handoff'

export interface DealActivityChange {
  field: string
  from: unknown
  to: unknown
}

export interface DealActivity {
  id: string
  action: DealActivityAction
  fromStageId: string | null
  toStageId: string | null
  actorUserId: string | null
  actorLabel: string | null
  changes: DealActivityChange[]
  createdAt: string
}

export interface InvoiceHandoffResult {
  dealId: string
  invoiceClientId: number
  invoiceQuoteId: number
  handoffAt: string
}
