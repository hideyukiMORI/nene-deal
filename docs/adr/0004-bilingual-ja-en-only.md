# ADR 0004: Bilingual JA/EN Only

## Status

Accepted

## Context

NeNe Deal serves small B2B teams operating **in Japan**, including a growing number of
foreign-founded or foreign-staffed companies doing business in the Japanese market. These
operators need an English UI to use the pipeline day to day.

At the same time, the domain itself — the won-deal handoff into NeNe Invoice — follows
**Japanese business and accounting conventions** (qualified-invoice rules, JPY amounts,
fiscal expectations). Those rules live in NeNe Invoice, and Deal feeds that Japan-specific
billing chain.

Supporting arbitrary locales would signal that the product fits non-Japanese accounting and
business contexts. It does not. Broad localization would create expectations the product
deliberately will not meet, so the supported locale set is bounded on purpose.

## Decision

- The product UI/runtime supports **exactly two locales: Japanese (`ja`) and English (`en`)**.
- `ja` is the primary/default locale; `en` is a first-class peer for foreign operators in Japan.
- Message catalogs ship `ja` + `en` only. Adding a third locale requires superseding this ADR.
- This governs **product UI and runtime messages**, not repository documentation. Repository
  Markdown remains English per `AGENTS.md` (Issues/PRs/commits may be Japanese).

## Consequences

- Clear scope signal: NeNe Deal is a Japan-market tool with English access, not a global CRM.
- i18n message catalogs (Kanban UI scaffold work) are bounded to two locales — simpler review and QA.
- Amounts, dates, and handoff semantics stay aligned with Japanese conventions and NeNe Invoice.

## Related

- `docs/explanation/product-vision.md`
- `docs/explanation/scope-contract.md`
- `docs/explanation/terminology.md`
- Current work and hand-off (Kanban UI scaffold — i18n catalogs): the private
  mirror `nene-origin/internal-docs/deal/todo/`, not a path in this repo — the
  operational logs moved there in `#146`/`#147` (see `AGENTS.md`)
