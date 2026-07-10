# Current TODO

**MVP + polish complete; demo packaging landed — next lane: Suite catalog / NENE2 Demo module adoption**

## Done

### Phase 1 — MVP pipeline

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

### Post-MVP polish (2026-06)

- [x] Issue #39: Local dev ports pinned to the family-unique `81**` block
- [x] Issue #40: SQLite-compatible timestamp in the operator-admin migration
- [x] Issue #43 / #45: Calm design-system redesign — split login, mobile layout, brand logo/favicon, verified layout fixes
- [x] Issue #47: Post-redesign features — audit log + soft delete (recycle bin, `tools/purge-trash.php`), deal owners, forecast closing day, org settings, deal timeline
- [x] Issue #49 / #51: Production JWT fail-close — unconditional auth stage + NENE2 `GuardedJwtSecretResolver` (dev secret only behind `NENE2_ALLOW_DEV_SECRET=1`)
- [x] Issue #53: Audit CSV export via NENE2 `CsvWriter` (formula-injection closed)
- [x] Issue #55: NENE2 conformance linter in the `composer check` gate
- [x] Issue #57: Clock sweep — raw time reads migrated to NENE2 `ClockInterface`
- [x] Issue #59: GitHub Actions CI (backend `composer check` / frontend `npm run check` + audit)

### Demo packaging (2026-07)

- [x] Issue #61: Demo seed + reset — `tools/seed-demo.php` (T-relative dates, funnel spread, stage history, demo accounts; re-run = reset; `--org` ready for disposable orgs)
- [x] Issue #62: Production-shaped container (`docker/php/Dockerfile.prod`, `compose.prod.yaml`, SPA served same-origin via `public_html/.htaccess`), phinx moved to runtime deps, HETEML artifact builder (`tools/build-heteml-artifact.sh`), demo runbook `docs/demo.md`
- [x] Issue #65: Browser installer (invoice/clear/vault shape, vault #109/#120 Tier A form) — `public_html/install.php` + `installer.js`, NENE2 install toolkit (`EnvironmentWriter` 0640 / `ReInstallationGuard` marker+DB probe / in-process `DatabaseSchemaApplier`), `src/Install/` (`InstallEnvironment` / `DatabaseProvisioningProbe` / `AdminProvisioner`); deletes the dev-seeded `operator@nene-deal.test`, admin password memory-only, self-deletes on success

## Next

- [ ] Adopt the NENE2 `Demo` module (disposable per-visitor demo orgs, as in nene-invoice/nene-clear) to replace the fixed demo org — planned once adopted fleet-wide
- [ ] Suite catalog entry
- [ ] Optional hardening for a public demo box: rate limiting on `/api/v1/auth/login`

## Handoff

Public repo `hideyukiMORI/nene-deal`. Pipeline SSOT only — Clear docs must not be copied here.
Demo operations (start, credentials, reset, shared-hosting install): `docs/demo.md`.

Last updated: 2026-07-10
