# Workflow

Issue-driven development. Inherits [NENE2 workflow](https://github.com/hideyukiMORI/NENE2/blob/main/docs/workflow.md).

## Standard flow

1. Create or reuse a GitHub Issue.
2. Read `docs/todo/current.md` and `docs/roadmap.md`.
3. Branch from `main`: `type/issue-number-summary`.
4. Implement minimal change; update docs when boundaries shift.
5. Run relevant checklist in `docs/review/`.
6. Conventional Commit with `(#issue)`.
7. PR with `Closes #number` → merge → sync `main`.

Do **not** commit directly to `main`.

## Phase 0 order

1. Governance (this bootstrap)
2. OpenAPI contract — deals, stages, forecast, handoff
3. PHP scaffold + first vertical slice
4. Admin UI (kanban)

Last updated: 2026-05-30
