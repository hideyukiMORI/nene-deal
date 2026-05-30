import type { DealId } from './ids'

export interface Deal {
  id: DealId
  accountLabel: string
  amountCents: number
  stageId: string
  stageSlug: string | null
  probabilityPercent: number
  expectedCloseDate: string | null
  note: string | null
  invoiceClientId: number | null
  invoiceQuoteId: number | null
  handoffAt: string | null
}

export interface CreateDealInput {
  accountLabel: string
  amountCents: number
  /** Target stage as slug or ULID. */
  stageRef: string
  probabilityPercent: number
}

export interface UpdateDealInput {
  accountLabel: string
  amountCents: number
  probabilityPercent: number
  note: string | null
}

export interface InvoiceHandoffResult {
  dealId: string
  invoiceClientId: number
  invoiceQuoteId: number
  handoffAt: string
}
