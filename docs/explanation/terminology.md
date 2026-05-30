# Terminology (binding)

Single source of truth for NeNe Deal identifiers. Typos and unregistered variants
**block merge**.

## Product names (display)

| Canonical | Never |
| --- | --- |
| **NeNe Deal** | NeNe deal, nene deal, NeneDeal (display) |
| **NeNe Invoice** | Invoice alone when product boundary matters |
| **NeNe Clear** | Clear alone when distinguishing from Deal |

## Repository and package

| Canonical | Never |
| --- | --- |
| Repo `nene-deal` | nene_deal, nene-sales |
| Catalog id `nene-deal` | `nene-clear`, `nene-invoice` (wrong product) |
| PHP namespace `NeneDeal\` | `NeneClear\`, `NeNeClear\` |

## Core domain

| Term | Meaning |
| --- | --- |
| **deal** | One sales opportunity (card on the board) |
| **pipeline** | All open deals for an organization |
| **stage** | Step in the sales process (e.g. qualified, proposal) |
| **probability** | Win likelihood (0–100 or A/B/C mapped to %) |
| **forecast** | Weighted sum `amount_cents × probability` — approximate |
| **won** | Terminal stage triggering optional Invoice handoff |
| **lost** | Terminal stage — no Invoice handoff |
| **handoff** | HTTP creation of draft Invoice client/quote — not payment |

## Fields (API / DB)

| Field | Type | Notes |
| --- | --- | --- |
| `amount_cents` | integer | JPY cents; no floats |
| `account_label` | string | Display name until Invoice client exists |
| `stage_id` | ULID or slug | FK to stage definition |
| `probability_percent` | 0–100 | Integer |
| `expected_close_date` | date optional | Forecast hint |
| `invoice_client_id` | int nullable | Set after handoff — Invoice PK |
| `invoice_quote_id` | int nullable | Set after handoff — Invoice PK |
| `organization_id` | UUID/ULID | Tenant scope |

## Forbidden in Deal docs/code (wrong product domain)

Do **not** use these as **Deal-owned** concepts:

- reconciliation, 消込, match line, bank line, dunning, 督促
- StandardTransaction, mapping preset (Profile)
- qualified invoice, 適格請求, invoice registration number (Invoice issuer domain)
- vault document, retention years (Vault)

Cross-references to siblings are allowed in integration docs **only** to state boundaries.

## Localization (binding)

| Locale | Role |
| --- | --- |
| `ja` | Primary / default |
| `en` | First-class peer (foreign operators in Japan) |

Product UI ships **`ja` and `en` only**. Other locales are out of scope — the domain follows
Japanese business and accounting conventions ([ADR 0004](../adr/0004-bilingual-ja-en-only.md)).
This binds **product UI/runtime strings**, not repository docs (English; see `AGENTS.md`).

## Stage defaults (Phase 1 seed)

Register in migrations as `lead`, `qualified`, `proposal`, `negotiation`, `won`, `lost`
— adjust via ADR if changed.

Last updated: 2026-05-30
