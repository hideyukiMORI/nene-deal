import type { CreateDealDto, DealDto } from './api-types'
import { toDealId } from './ids'
import type { CreateDealInput, Deal } from './model'

export function mapDealDtoToModel(dto: DealDto): Deal {
  return {
    id: toDealId(dto.id),
    accountLabel: dto.account_label,
    amountCents: dto.amount_cents,
    stageId: dto.stage_id,
    stageSlug: dto.stage_slug ?? null,
    probabilityPercent: dto.probability_percent,
  }
}

export function mapCreateInputToDto(input: CreateDealInput): CreateDealDto {
  return {
    account_label: input.accountLabel,
    amount_cents: input.amountCents,
    stage_id: input.stageRef,
    probability_percent: input.probabilityPercent,
  }
}
