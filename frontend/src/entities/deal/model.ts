import type { DealId } from './ids'

export interface Deal {
  id: DealId
  accountLabel: string
  amountCents: number
  stageId: string
  stageSlug: string | null
  probabilityPercent: number
}

export interface CreateDealInput {
  accountLabel: string
  amountCents: number
  /** Target stage as slug or ULID. */
  stageRef: string
  probabilityPercent: number
}
