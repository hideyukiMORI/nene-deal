# Domain Model (Phase 1)

High-level entities for NeNe Deal. DDL lands with OpenAPI + migrations Issues.

## Entities

### Deal

| Attribute | Notes |
| --- | --- |
| `id` | ULID |
| `organization_id` | Tenant |
| `account_label` | Buyer display name |
| `amount_cents` | Expected deal value |
| `stage_id` | Current pipeline stage |
| `probability_percent` | 0–100 |
| `expected_close_date` | Optional |
| `owner_user_id` | Optional assignee |
| `note` | Short text; not a CRM activity feed |
| `invoice_client_id` | Nullable; after handoff |
| `invoice_quote_id` | Nullable; after handoff |
| `created_at` / `updated_at` | |

### PipelineStage

| Attribute | Notes |
| --- | --- |
| `id` | ULID |
| `organization_id` | Tenant |
| `slug` | e.g. `proposal` |
| `label` | Display |
| `sort_order` | Kanban column order |
| `is_terminal` | `won` / `lost` |
| `is_won` | True only for won stage |

### DealStageHistory (audit-lite)

| Attribute | Notes |
| --- | --- |
| `deal_id` | |
| `from_stage_id` | Nullable on create |
| `to_stage_id` | |
| `actor_user_id` | |
| `created_at` | |

### ForecastSnapshot (optional Phase 1 — may compute on read)

Weighted total for calendar month from open deals with `expected_close_date` in range.

## Aggregates

- **Deal/** — CRUD, move stage, won handoff use case
- **Pipeline/** — list stages, kanban board query, forecast query

## Not in Deal DB

- Invoice line items, tax rates, payments
- Bank transactions, reconciliation groups
- Document blobs

Last updated: 2026-05-30
