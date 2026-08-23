# Invoice Handoff Contract (binding)

NeNe Deal **won** stage triggers an **optional, operator-confirmed** HTTP integration
with [NeNe Invoice](https://github.com/hideyukiMORI/nene-invoice). This document is
the Deal-side contract. Invoice SSOT rules remain in Invoice docs.

> **Handoff target is Invoice only.** Deal does not call NeNe Clear — reconciliation is
> downstream of Invoice and out of scope here (see [sibling products](sibling-products.md)).

## Principles

| # | Rule |
| --- | --- |
| 1 | **Invoice is billing SSOT** after handoff |
| 2 | Deal stores **link ids only** — not quote line copies |
| 3 | **HTTP only** — no shared database |
| 4 | **Draft only** — Deal creates draft client + draft quote |
| 5 | **Explicit operator action** — no auto-handoff on stage drag alone (confirm step) |
| 6 | **Idempotent** — repeat handoff returns existing link or 409 with guidance |

## Trigger

Operator marks deal **won** (terminal won stage) and clicks **Send to Invoice**
(or API `POST /api/v1/deals/{id}/invoice-handoff`).

 Preconditions:

- Deal not already linked (`invoice_quote_id` null) OR idempotent retry documented
- Invoice base URL + credentials configured (`NENE_DEAL_INVOICE_BASE_URL`, service token)
- Amount and account label present on deal

## HTTP calls (Deal → Invoice)

Sequence (Invoice admin API):

1. `POST /admin/clients` — draft client from `account_label` (+ optional address fields Phase 2)
2. `POST /admin/quotes` — draft quote with `client_id`, headline amount mapped to line item(s)

### Wire shape (binding)

🔴 This section exists because its absence caused `#212`. The sentence above —
"headline amount mapped to line item(s)" — never named a single field, so the
implementation guessed three of them wrong and **every handoff was a 422 from
the day it was written**. An underspecified contract is not a lenient one; it
just moves the specification into whoever writes the client first.

Measured against `nene-invoice` `origin/main` on **2026-08-23**
(`LineItemRequest::parseLines`, `CreateQuoteHandler`, `CreateQuoteUseCase`):

```jsonc
// POST /admin/clients
{ "name": "<account_label>" }              // `name` is the only required field

// POST /admin/quotes
{
  "client_id": 4821,                       // required
  "line_items": [                          // required, non-empty — NOT `lines`
    {
      "description": "<account_label>",    // required, non-empty string
      "quantity": 1,                       // required, int
      "unit_price_cents": 250000,          // required, int — NOT `unit_amount_cents`
      "tax_rate_bps": 1000                 // required, int, and only 800 or 1000
    }
  ]
}
```

| Trap | Detail |
| --- | --- |
| `tax_rate_bps` is not free-form | `ALLOWED_TAX_RATES_BPS = [800, 1000]`. `0` is **rejected**, not neutral. See the constant in `HttpInvoiceClient` — it is provisional and disappears when Invoice owns the rate |
| `currency` is not read | Deal sent `"currency": "JPY"` for months; `CreateQuoteHandler` never looks at it. Removed in `#212` — a field the receiver ignores reads as contract and is not one |
| Amounts are **whole yen** | `*_cents` is the minor unit; JPY has zero decimals (`#81`). `unit_price_cents: 250000` is ¥250,000 |
| Optional, unused by Deal | `valid_until`, `notes` |

Both endpoints return the new id at **`id`** on the top-level object.

⚠️ Deal pins this shape in `tests/Handoff/HttpInvoiceClientTest.php`. That test
proves **Deal matches what was measured**; it cannot see Invoice changing the
contract. The live cross-check is QA `#168` A-3 (not yet run).

Deal **does not**:

- `POST /admin/invoices` directly in MVP (quote → invoice stays in Invoice UI)
- write payments or reconciliation records

## Deal persistence after success

Update deal row:

- `invoice_client_id`
- `invoice_quote_id`
- optional `handoff_at`, `handoff_actor_user_id`

Record stage/history audit entry `deal.invoice_handoff_completed`.

## Failure handling

| Case | Deal behavior |
| --- | --- |
| Invoice 4xx | Surface Problem Details to operator; deal stays won, unlinked |
| Invoice 5xx | Retry-safe; no partial link without compensating transaction |
| Network | No link ids written |

## Configuration (env)

| Variable | Purpose |
| --- | --- |
| `NENE_DEAL_INVOICE_BASE_URL` | Invoice public/admin base |
| `NENE_DEAL_INVOICE_BEARER_TOKEN` | Machine token (secret — not in git) |

Suite mode: may inherit sibling URL from `NENE_SUITE_APP_NENE_INVOICE_URL` when documented in suite catalog.

## Cross-repo changes

Invoice API gaps require Issues in **nene-invoice**, not workarounds in Deal DB.

## Related

- [ADR 0003](../adr/0003-invoice-http-handoff-on-won-deal.md)
- Invoice OpenAPI: `nene-invoice/docs/openapi/openapi.yaml`

Last updated: 2026-05-30
