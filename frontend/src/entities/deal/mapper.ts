import type {
  CreateDealDto,
  DealActivityDto,
  DealDto,
  InvoiceHandoffResultDto,
  UpdateDealDto,
} from './api-types'
import { toDealId } from './ids'
import type {
  CreateDealInput,
  Deal,
  DealActivity,
  DealActivityChange,
  InvoiceHandoffResult,
  UpdateDealInput,
} from './model'

export function mapDealDtoToModel(dto: DealDto): Deal {
  return {
    id: toDealId(dto.id),
    accountLabel: dto.account_label,
    amountCents: dto.amount_cents,
    stageId: dto.stage_id,
    stageSlug: dto.stage_slug ?? null,
    probabilityPercent: dto.probability_percent,
    expectedCloseDate: dto.expected_close_date ?? null,
    ownerLabel: dto.owner_label ?? null,
    note: dto.note ?? null,
    invoiceClientId: dto.invoice_client_id ?? null,
    invoiceQuoteId: dto.invoice_quote_id ?? null,
    handoffAt: dto.handoff_at ?? null,
    deletedAt: dto.deleted_at ?? null,
  }
}

export function mapCreateInputToDto(input: CreateDealInput): CreateDealDto {
  return {
    account_label: input.accountLabel,
    amount_cents: input.amountCents,
    stage_id: input.stageRef,
    probability_percent: input.probabilityPercent,
    expected_close_date: input.expectedCloseDate,
  }
}

export function mapUpdateInputToDto(input: UpdateDealInput): UpdateDealDto {
  return {
    account_label: input.accountLabel,
    amount_cents: input.amountCents,
    probability_percent: input.probabilityPercent,
    note: input.note,
    expected_close_date: input.expectedCloseDate,
  }
}

export function mapActivityDtoToModel(dto: DealActivityDto): DealActivity {
  const changes: DealActivityChange[] = Object.entries(dto.changes ?? {}).map(
    ([field, change]) => ({ field, from: change.from, to: change.to }),
  )

  return {
    id: dto.id,
    action: dto.action as DealActivity['action'],
    fromStageId: dto.from_stage_id ?? null,
    toStageId: dto.to_stage_id ?? null,
    actorUserId: dto.actor_user_id ?? null,
    actorLabel: dto.actor_label ?? null,
    changes,
    createdAt: dto.created_at,
  }
}

export function mapHandoffResultDtoToModel(dto: InvoiceHandoffResultDto): InvoiceHandoffResult {
  return {
    dealId: dto.deal_id,
    invoiceClientId: dto.invoice_client_id,
    invoiceQuoteId: dto.invoice_quote_id,
    handoffAt: dto.handoff_at,
  }
}
