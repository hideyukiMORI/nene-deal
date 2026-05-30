# ADR 0003: Invoice HTTP Handoff on Won Deal

## Status

Accepted

## Context

Operators need a lightweight path from **won deal** to **draft billing artifacts**
without duplicating Invoice domain logic in Deal.

## Decision

On operator-confirmed won deal:

1. Deal calls Invoice HTTP API (`POST /admin/clients`, `POST /admin/quotes`).
2. Deal persists `invoice_client_id` and `invoice_quote_id` only.
3. No direct Clear calls; no shared database with Invoice.

Idempotent retries; failures leave deal won but unlinked.

## Consequences

- Invoice OpenAPI gaps become Issues in `nene-invoice`.
- Quote → invoice conversion stays in Invoice UI/API.

## Related

- `docs/integrations/invoice-handoff-contract.md`
- NeNe Invoice OpenAPI
