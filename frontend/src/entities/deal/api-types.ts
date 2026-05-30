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
  note?: string | null
  invoice_client_id?: number | null
  invoice_quote_id?: number | null
  handoff_at?: string | null
  created_at: string
  updated_at: string
}

export interface CreateDealDto {
  account_label: string
  amount_cents: number
  stage_id: string
  probability_percent?: number
}

export interface StageChangeDto {
  to_stage_id: string
}
