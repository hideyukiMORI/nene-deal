# Product Vision

NeNe Deal is a **self-hosted B2B deal pipeline** on [NENE2](https://github.com/hideyukiMORI/NENE2).
It fills the gap between **“we use spreadsheets for sales”** and **“Salesforce is overkill”**
in the NeNe product family.

## Problem

Small B2B companies (5–50 staff) often:

- track deals in spreadsheets or chat — pipeline invisible to leadership
- pay for HubSpot/SFA features nobody fills in
- re-type customer and amount into invoicing tools when a deal closes

NeNe Deal optimizes for **minimum fields that actually get updated** and a
**board the CEO can read in 30 seconds**.

## North Star

Operators can:

1. Create a **deal card** (account, amount, stage, probability).
2. Drag deals across a **kanban** by stage.
3. See **this month’s weighted landing** (rough forecast).
4. Mark **won** and **push draft client + quote to NeNe Invoice** in one explicit action.
5. Open Invoice to finish line items, tax, and PDF — Deal does not replace that step.

## Non-goals

- Marketing automation, email sequences, CPQ, contracts CLM
- Full CRM (contacts graph, activities timeline, call logging) in MVP
- Reconciliation, bank import, or dunning (**NeNe Clear**)
- Being billing or tax SSOT (**NeNe Invoice**)

## Primary persona

> A **10-person B2B services firm** sells project work. The sales lead updates
> five deal cards weekly. The CEO opens Deal before the Monday meeting to see
> stage and ¥ landing. When a project is won, they click **Send to Invoice**,
> tweak the quote in Invoice, and send the PDF — without re-typing the client name
> and headline amount.

## Ecosystem fit

```text
Deal (pipeline) ──won──► Invoice (billing) ──► Clear (reconciliation, optional)
```

Deal is intentionally **lighter on compliance** than Invoice or Vault. It records
**sales intent and stage history**, not statutory billing evidence.

## Success criteria (Phase 1)

- Kanban + forecast API and admin UI for one organization
- Won → Invoice draft handoff documented and working against Invoice admin API
- Standalone Docker install; Suite catalog entry documented when ready

Last updated: 2026-05-30
