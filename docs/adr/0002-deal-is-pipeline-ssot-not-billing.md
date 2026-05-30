# ADR 0002: Deal Is Pipeline SSOT, Not Billing

## Status

Accepted

## Context

The NeNe family splits domains: Invoice owns quotes/invoices; Clear owns reconciliation
after payment. Deal must not become a second billing or collections system.

## Decision

NeNe Deal owns:

- Deal cards (account label, amount, stage, probability)
- Pipeline stages and stage history
- Kanban and monthly forecast views

NeNe Deal does **not** own:

- Tax, invoice numbering, payment capture
- Bank CSV import, reconciliation, dunning (NeNe Clear)
- Quote/invoice line-item SSOT (NeNe Invoice)

Deal stores **foreign link ids** to Invoice after handoff only.

## Consequences

- Clear integration docs are out of scope for this repo.
- Won-deal flow is a single HTTP handoff to Invoice (ADR 0003).

## Related

- `docs/explanation/scope-contract.md`
- `docs/explanation/terminology.md`
