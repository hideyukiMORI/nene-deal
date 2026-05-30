# Current TODO

**Phase 1 — MVP pipeline (backend complete; frontend underway)**

## Done

- [x] Issue #1: Repository bootstrap — initial commit
- [x] Issue #3: Bilingual JA/EN policy (ADR 0004) + nene-clear leftover cleanup
- [x] Issue #2: OpenAPI contract — `docs/openapi/openapi.yaml`
- [x] Issue #10: OpenAPI migrated to 3.1.0 + validator in `composer check`
- [x] Issue #6: NENE2 consumer scaffold (`composer.json`, health)
- [x] Issue #12: Deal & Pipeline domain API — CRUD, stage move, history, `GET /stages`
- [x] Issue #14: Read models — `GET /board` (kanban) + `GET /forecast`
- [x] Issue #16: Won → Invoice handoff (`POST /deals/{id}/invoice-handoff`)
- [x] Issue #5 / #18: Kanban board frontend (React, JA/EN i18n) — `frontend/` per nene-records standards
- [x] Issue #20: Docker Compose dev stack (PHP app + MySQL)
- [x] Issue #22: Auth (machine API key) + multi-tenant resolution (request-scoped org)
- [x] Issue #24: Frontend deal detail/edit + won → Invoice handoff action

- [x] Issue #26: Wire frontend↔backend — `/api/v1` route prefix + frontend org/API-key headers

## Next

- [ ] Operator login (JWT) + RBAC (future epic)
- [ ] Phase 2: Suite catalog entry, MCP read tools, stage customization UI

## Handoff

Public repo `hideyukiMORI/nene-deal`. Pipeline SSOT only — Clear docs must not be copied here.

Last updated: 2026-05-30
