# Roadmap

## Phase 0 — Governance (current)

- [x] Repository bootstrap, scope, terminology, Invoice handoff contract
- [x] OpenAPI contract (Issue #2) — `docs/openapi/openapi.yaml`
- [ ] Backend + frontend standards (inherit NENE2)

## Phase 1 — MVP pipeline

- [x] Deal CRUD + stage move + kanban API — CRUD / stage move / history / `GET /stages` (#12) + `GET /board` (#14)
- [x] Monthly forecast endpoint — `GET /forecast` (#14)
- [x] Won → Invoice handoff (draft client + quote) — `POST /deals/{id}/invoice-handoff` (#16)
- [ ] Docker Compose dev stack
- [x] Admin kanban UI — `frontend/` React SPA (#18)
- [x] Bilingual (JA/EN) UI + `ja`/`en` message catalogs (ADR 0004) — `frontend/src/shared/i18n` (#18)

## Phase 2 — Polish

- [ ] Suite catalog entry
- [ ] MCP read tools
- [ ] Stage customization UI

## Non-goals

- Clear reconciliation features
- Full HubSpot parity
- UI locales beyond Japanese and English (ADR 0004)

Last updated: 2026-05-30
