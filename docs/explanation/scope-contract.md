# Scope Contract (binding)

This document defines what NeNe Deal **is** and **is not**. PR review must respect it.

> **Terminology:** identifiers **MUST** match [`terminology.md`](./terminology.md).

## GOAL

Provide an **ultra-light B2B deal pipeline** so small teams track opportunities in a
kanban, see a **rough monthly landing**, and **hand off won deals** to NeNe Invoice
as draft billing records — without adopting a full SFA/CRM suite.

Deal answers: *who, how much, what stage, how likely, when might it close?*  
Invoice answers: *what is the legal quote/invoice and tax treatment?*

## DO

| # | Deal owns |
| --- | --- |
| D1 | **Deal** entity: account label, `amount_cents`, stage, probability, owner, notes |
| D2 | **Pipeline stages** (configurable list; default set documented in domain model) |
| D3 | **Kanban** API + admin UI (move stage, filter by owner) |
| D4 | **Forecast summary** — weighted pipeline for current month (approximate) |
| D5 | **Won handoff** — operator-triggered HTTP call to Invoice for draft client + quote |
| D6 | Store **Invoice link ids** (`invoice_client_id`, `invoice_quote_id`) after handoff |
| D7 | **Stage change audit** — who moved which deal when (lightweight; not Vault-grade) |
| D8 | Multi-tenant `organization_id` (same pattern as sibling products) |
| D9 | OpenAPI-first JSON API; optional MCP read tools Phase 2+ |
| D10 | Standalone install + optional NeNe Suite catalog entry (when suite documents it) |
| D11 | **Bilingual UI (JA/EN)** — Japanese default, English peer for foreign operators in Japan ([ADR 0004](../adr/0004-bilingual-ja-en-only.md)) |

## DON'T

| # | Deal must NOT | Belongs to |
| --- | --- | --- |
| X1 | Issue final quotes or invoices | **NeNe Invoice** |
| X2 | Compute consumption tax / qualified invoice fields | **NeNe Invoice** |
| X3 | Record payments or AR balances | **NeNe Invoice** |
| X4 | Match bank deposits to invoices | **NeNe Clear** |
| X5 | Send dunning / reconciliation workflows | **NeNe Clear** |
| X6 | Import or normalize bank CSV | **NeNe Profile** → Clear |
| X7 | Store received PDFs / 電帳法 archive | **NeNe Vault** |
| X8 | Share database with Invoice or Clear | HTTP only |
| X9 | Auto-handoff to Invoice without operator confirmation | Product rule |
| X10 | Claim tax or statutory billing compliance | Operator + Invoice + professionals |
| X11 | Ship UI locales beyond Japanese and English | Product rule ([ADR 0004](../adr/0004-bilingual-ja-en-only.md)) |

## Won-deal handoff (summary)

Binding detail: [`../integrations/invoice-handoff-contract.md`](../integrations/invoice-handoff-contract.md).

Deal sends **draft** resources only. Invoice remains billing SSOT after handoff.

## Related

- [ADR 0002](../adr/0002-deal-is-pipeline-ssot-not-billing.md)
- [ADR 0003](../adr/0003-invoice-http-handoff-on-won-deal.md)
- [ADR 0004](../adr/0004-bilingual-ja-en-only.md)
- [`domain-model.md`](./domain-model.md)

Last updated: 2026-05-30
