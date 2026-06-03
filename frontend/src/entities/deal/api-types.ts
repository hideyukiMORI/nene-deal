export interface DealDto {
  id: string
  organization_id: string
  account_label: string
  amount_cents: number
  stage_id: string
  stage_slug?: string | null
  probability_percent: number
  expected_close_date?: string | null
  owner_user_id?: string | null
  owner_label?: string | null
  note?: string | null
  invoice_client_id?: number | null
  invoice_quote_id?: number | null
  handoff_at?: string | null
  created_at: string
  updated_at: string
  deleted_at?: string | null
}

export interface CreateDealDto {
  account_label: string
  amount_cents: number
  stage_id: string
  probability_percent?: number
  expected_close_date?: string | null
}

export interface StageChangeDto {
  to_stage_id: string
}

export interface DealActivityDto {
  id: string
  deal_id: string
  action: string
  from_stage_id?: string | null
  to_stage_id?: string | null
  actor_user_id?: string | null
  actor_label?: string | null
  changes?: Record<string, { from: unknown; to: unknown }> | null
  created_at: string
}

export interface DealActivityListDto {
  data: DealActivityDto[]
}

export interface UpdateDealDto {
  account_label: string
  amount_cents: number
  probability_percent: number
  note: string | null
  expected_close_date?: string | null
}

export interface InvoiceHandoffResultDto {
  deal_id: string
  invoice_client_id: number
  invoice_quote_id: number
  handoff_at: string
  handoff_actor_user_id?: string | null
}
