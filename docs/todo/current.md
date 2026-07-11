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
- [x] Issue #73: Installer `--export-patterns` CLI (vault #131 shape) — 10 screen states + 9 component-part pages + index machine-generated from the production renderer; handoff zip `_work/assets/nene-deal-installer-design-handoff-2026-07-10.zip`
- [x] Issue #74: ClaudeDesign visual design applied (clear #273 / vault #134 shape) — deep-teal brand system via `installer_css()` + brand mark + favicon swap only; all 20 re-exported patterns normalized-identical to the delivery, `installer.js` untouched
- [x] Issue #78: Probe reads `.env` via phpdotenv (`Dotenv::parse`, same dialect as ConfigLoader) — `parse_ini_file` choked on `#`-comments/parens on the live host, warnings broke the guard's 403 into a 200 with path leak; unreadable `.env` now degrades silently to an empty env (marker layer still guards). Live `install.php` (restored by a post-install rsync) deleted from HETEML

- [x] Issue #69: NENE2 `Nene2\Demo` consumer (v1.10.0) — disposable per-visitor demo orgs: `GET /demo/standard` (fail-close `DEMO_MODE`), JWT seat page → `sessionStorage` one-shot import (clear #275 shape), `FileRateLimitStorage` throttle 30/h + 200-org ceiling, branded HTML error pages (invoice #617 shape), `tools/sweep-demo.php` (TTL 3h), claim-based tenant resolution (bearer before org middleware; signed `org` claim authoritative), shared `DemoPipelineFixture` with the fixed-org seed; fixed demo org + reseed kept coexisting

### Structural audit remediation (2026-07-11, #89/#90/#91)

- [x] Issue #90: `users.status` (active/disabled) + login status check + timing-equalized credential errors + disable/enable in user management (alternative to DELETE — preserves stage-history attribution)
- [x] Issue #95: Login rate limiting — product-local port of clear's `PdoLoginThrottle` (email+IP, 5 failures / 15 min → 15 min lock, 429 `too-many-login-attempts`). Replaces the #69 deferral; swap for the NENE2 path-scoped ThrottleMiddleware when it ships upstream
- [x] Issue #89: `Nene2\Audit` consumer — append-only `audit_events` (canonical shape, ULID string actor/org ids), every mutation use case records (deal CRUD/stage move/restore, handoff, stage CRUD, user CRUD, settings, login success/failure). `deal_stage_history` kept as domain history (ADR 0005); action vocabulary in terminology registry

## Next

- [ ] Suite catalog entry
- [ ] Structural audit 2026-07-11 follow-ups: roll-up checklist #91 (summary: `docs/review/2026-07-11-structural-audit.md`, #92)

## Handoff

Public repo `hideyukiMORI/nene-deal`. Pipeline SSOT only — Clear docs must not be copied here.
Demo operations (start, credentials, reset, shared-hosting install): `docs/demo.md`.

Last updated: 2026-07-11
