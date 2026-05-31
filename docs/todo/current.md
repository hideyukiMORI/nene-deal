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
- [x] Issue #28: Operator JWT login (backend) — users + login + `/me` + bearer middleware
- [x] Issue #30: Operator login UI + auth gate (frontend)
- [x] Issue #32: RBAC roles (`admin`/`operator`) + user management API + UI
- [x] Issue #34: MCP read-only tool catalog (7 tools) + `composer mcp` validator + local server docs
- [x] Issue #36: Stage customization — create, rename, reorder, delete (admin)

## Next (Phase 2)

- [ ] Suite catalog entry

## Handoff

Public repo `hideyukiMORI/nene-deal`. Pipeline SSOT only — Clear docs must not be copied here.

Last updated: 2026-05-31
