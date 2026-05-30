# Current TODO

**Phase 1 — MVP pipeline (backend underway)**

## Done

- [x] Issue #1: Repository bootstrap — initial commit
- [x] Issue #3: Bilingual JA/EN policy (ADR 0004) + nene-clear leftover cleanup
- [x] Issue #2: OpenAPI contract — `docs/openapi/openapi.yaml`
- [x] Issue #10: OpenAPI migrated to 3.1.0 + validator in `composer check`
- [x] Issue #6: NENE2 consumer scaffold (`composer.json`, health)
- [x] Issue #12: Deal & Pipeline domain API — CRUD, stage move, history, `GET /stages`
- [x] Issue #14: Read models — `GET /board` (kanban) + `GET /forecast`

## Next

- [ ] Won → Invoice handoff (`POST /deals/{id}/invoice-handoff`)
- [ ] Authentication + multi-tenant resolution (replace single-org resolver)
- [ ] Issue #5: Kanban UI scaffold + JA/EN i18n message catalogs (ADR 0004 — `ja`/`en` only)

## Handoff

Public repo `hideyukiMORI/nene-deal`. Pipeline SSOT only — Clear docs must not be copied here.

Last updated: 2026-05-30
