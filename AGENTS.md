# Agent / AI Guide

Entry point for AI agents working on **NeNe Deal** (public repo `nene-deal`).

## Domain (read first)

| Product | Repository | Domain |
| --- | --- | --- |
| **NeNe Deal** | `nene-deal` (this) | B2B deal pipeline (kanban, forecast) |
| **NeNe Invoice** | `nene-invoice` | Billing SSOT — clients, quotes, invoices |
| **NeNe Clear** | `nene-clear` | Reconciliation & dunning — **not Deal** |

See [ADR 0002](docs/adr/0002-deal-is-pipeline-ssot-not-billing.md).

## Read First

- **Scope contract (binding):** `docs/explanation/scope-contract.md`
- **Terminology (binding):** `docs/explanation/terminology.md` — check before any identifier
- **Product vision:** `docs/explanation/product-vision.md`
- **Domain model:** `docs/explanation/domain-model.md`
- **Invoice handoff (binding):** `docs/integrations/invoice-handoff-contract.md`
- **NENE2 inheritance:** `docs/inheritance-from-nene2.md`
- **Coding standards index:** `docs/development/coding-standards.md`
- **Workflow:** `docs/workflow.md`
- **Current work:** `docs/todo/current.md`

## Operating Rules

- **Issue-driven** — create or reuse a GitHub Issue before substantive edits
- **No direct commits to `main`** — branch `type/issue-number-summary`
- **Do not** implement invoice line items, tax, qualified invoice PDFs — **Invoice**
- **Do not** implement bank reconciliation, payment matching, or dunning — **Clear**
- **Do not** copy Clear integration patterns into Deal docs or code (wrong upstream/downstream)
- **Do not** write to Invoice or Clear databases — HTTP handoff only
- **Namespace:** `NeneDeal\`; amounts: **integer cents** in API and DB
- **Product UI locales:** `ja`/`en` only (ADR 0004) — broader localization is a non-goal
- **Repository docs: English**; Issues/PRs/commits may use Japanese in description/body
- **No secrets** in git

## Local dev ports

The NeNe family is developed in parallel on one host, so each product owns a
**fixed, unique** port block. **NeNe Deal owns the `81**` block.** Do not fall
back to framework defaults (8080 / 3306 / 5173) — they collide with siblings.

| Service | Port | Set in |
| --- | --- | --- |
| App HTTP (Docker / `php -S`) | **8110** | `compose.yaml` (`NENE_DEAL_PORT`) |
| MySQL (Docker) | **3310** | `compose.yaml` (`NENE_DEAL_DB_PORT`) |
| Vite dev server | **5187** | `frontend/vite.config.ts` (`strictPort`) |
| Storybook | **6106** | `frontend/package.json` |

Reserved by siblings (avoid): `82**`/3316 NENE2, `83**`/5173 Clear, `84**`/3409
Profile, `85**`/5185 Invoice, `86**`/5186 Vault, `87**`/3790 Concierge,
`88**`/3390/5188 Suite, `89**`/3389/5271 Corpus, `180**` Records.

When adding a new dev service, pick a free port in the `81**` block and add it
to this table — never reuse a framework default.

## Framework

[NENE2](https://github.com/hideyukiMORI/NENE2) via Composer when runtime lands.
Reference consumer: [nene-invoice](https://github.com/hideyukiMORI/nene-invoice),
[nene-records](https://github.com/hideyukiMORI/nene-records).
