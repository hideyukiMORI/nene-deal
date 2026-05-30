# Sibling Products

How NeNe Deal relates to other NeNe repositories. **Deal integration docs live here;
Clear/Profile/Vault integration docs live in those repos.**

## Position

| Product | Repo | Relationship to Deal |
| --- | --- | --- |
| **NeNe Deal** | `nene-deal` | Pipeline SSOT (this repo) |
| **NeNe Invoice** | `nene-invoice` | **Upstream billing** — won handoff target |
| **NeNe Clear** | `nene-clear` | **No direct integration in MVP** — downstream of Invoice |
| **NeNe Profile** | `nene-profile` | No MVP integration |
| **NeNe Vault** | `nene-vault` | No MVP integration |
| **NeNe Records** | `nene-records` | Optional future: link catalog SKU on deal card |
| **NeNe Suite** | `nene-suite` | Optional installer / apex launcher |

## Data flow (family)

```text
[ Deal ] ──handoff (won)──► [ Invoice ] ──payments──► [ Clear ]
                │                  │
                │                  └── quotes / invoices SSOT
                └── stages / forecast SSOT
```

Deal **never** writes to Clear. If documentation mentions Clear, it is **boundary
context only** — not an implementation target in `nene-deal`.

## Suite catalog (future)

When added to `nene-suite/catalog/apps.json`:

- `id`: `nene-deal`
- `requires`: may list `nene-invoice` as **recommended** for handoff (not hard DB dependency)
- separate database per ADR 0002 in suite orchestration docs

Last updated: 2026-05-30
