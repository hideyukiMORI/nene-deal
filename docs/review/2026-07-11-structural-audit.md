# Structural Consistency Audit — 2026-07-11 (Deal findings)

On 2026-07-11 the four NeNe products (invoice / clear / deal / vault) were
audited for structural consistency across six lenses: NENE2 usage,
auth/session, multi-tenancy, installer/distribution, frontend, and the demo
mechanism. This document records the **Deal-specific** summary. Every finding
below was re-verified against this repository's actual code (file:line) before
being filed.

Issue mapping: #89 (audit logging), #90 (account status), #91 (roll-up
checklist), #92 (this document).

## What is already fleet-consistent (no action)

- NENE2 Router / DI / ServiceProvider / RFC 9457 Problem Details skeleton;
  `RuntimeContainerFactory` is a near-zero-diff twin of invoice's.
- Pinned Packagist dependency `hideyukimori/nene2 ^1.10.0` (clear/vault are
  still on `@dev` path repos — deal is on the reproducible side of that split).
- `Nene2\Demo` consumer (#69): throttle 30/h via `FileRateLimitStorage`,
  200-org ceiling + 503, TTL 3h, fail-close `DEMO_MODE`, branded error pages,
  UTC-explicit sweep parsing (`tools/sweep-demo.php:60-64`).
- `Nene2\Install` core parts + `public_html` layout + 3-step wizard +
  re-installation guard (#65).
- `GuardedJwtSecretResolver` fail-close JWT wiring
  (`src/Http/RuntimeServiceProvider.php`), `BearerTokenMiddleware`, claims
  shape `sub`/`role`/`org`.
- Row-scoped tenancy: every tenant-table query filters `organization_id`
  (including JOIN conditions, `src/Deal/PdoDealRepository.php`).
- Frontend core stack (React 19 / Vite 8 / Tailwind 4 / TanStack Query / zod),
  single fetch module, X-Authorization dual-header bridge (#67/#68/#83).

## Strengths unique to Deal (worth preserving)

| Strength | Evidence |
| --- | --- |
| `src/Tenancy/` package is the fleet's best seed for upstreaming a scoped-repo tenancy module into NENE2 | `CurrentOrganization` (interface) + `HolderCurrentOrganization` (throws when unresolved = fail-close) + `FixedOrganization` (tests) — the most product-agnostic shape of the four products |
| Cleanest conformance posture in the fleet | `conformance.baseline.json` has zero `allow` / zero `ignore` entries |
| Most disciplined demo-seat handoff | `frontend/src/shared/auth/demo-seat.ts` — one-shot sessionStorage park, removed on import, token lives in memory only (#69) |
| Installer self-deletes on success (the target shape; invoice/clear still leave a guarded installer behind) | `public_html/install.php:1133-1134` (`@unlink(__FILE__)`) |

## Findings (divergences from the fleet standard)

| # | Finding | Evidence | Severity | Tracked in |
| --- | --- | --- | --- | --- |
| 1 | No mutation audit trail — `Nene2\Audit` unused (0 imports); only a read-only CSV export of `deal_stage_history` | `src/Audit/PdoAuditExportRepository.php:21-33` | High | #89 |
| 2 | `users` has no `status` column, so accounts cannot be disabled; login checks `password_verify` only | `database/migrations/20260531120000_create_users_table.php:11-21`, `src/Auth/LoginUseCase.php:29-34` | High | #90 |
| 3 | No login rate limiting — known, intentionally deferred from #69 (NENE2 `ThrottleMiddleware` lacks path scoping; that gap is the upstream requirement) | `src/Auth/LoginInput.php:9-12`, `docs/todo/current.md` (Next) | High | current.md (deferred); upstream tracked at workspace level |
| 4 | `Nene2\Validation` unused — handlers hand-write 422 responses (10 files), so no per-field `errors[]` structure | `src/User/CreateUserHandler.php:27-49` et al. | Medium | #91 (d) |
| 5 | Custom base64 offset `Cursor` pagination instead of `Nene2\Http\Pagination` (the other three products) | `src/Deal/Cursor.php`, `src/Deal/ListDealsHandler.php:37,56` | Medium | #91 (e) |
| 6 | No superadmin concept — roles are admin/operator only, `organization_id` NOT NULL; no cross-tenant management layer | `src/` grep | Medium | #91 (f) |
| 7 | `.htaccess` has neither the `E=HTTP_AUTHORIZATION` re-delivery nor hardening headers (CSP/HSTS); mitigated at runtime by `AuthorizationHeaderFallback` (`public_html/index.php:26`) | `public_html/.htaccess` | Medium | #91 (g) |
| 8 | `tools/build-heteml-artifact.sh` produces neither a zip nor a SHA-256 sidecar — no distribution integrity guarantee (invoice's `build-release.sh` is the template) | `tools/build-heteml-artifact.sh` | High | #91 (h) |
| 9 | No JST/UTC regression test pinning the sweep TTL parsing (a trap the fleet hit twice; clear/vault have the test) | `tools/sweep-demo.php:60-64`, `tests/` | Medium | #91 (i) |
| 10 | No multi-tenancy ADR — the ULID string(26) org PK (unique in the fleet) and the claim→header→sole-org resolution order are undocumented design decisions | `docs/adr/` (0001–0004 only), `database/migrations/20260530120000_create_organizations_table.php:12` | Medium | #91 (j) |
| 11 | Migration seeds well-known credentials (`operator@nene-deal.test` / `password`); the browser installer deletes them (#65) but the manual CLI-migrate path leaves them in place | `database/migrations/20260531130000_seed_default_operator.php:27-33`, `public_html/install.php:1216-1219` | Medium | #91 (k) |

## Cross-product notes

- Findings 3, 5, 8 and the `Tenancy/` strength all feed NENE2 upstream
  candidates (path-scoped throttle, pagination standardization, release-build
  template, `Nene2\Tenancy`). Those are tracked at the workspace/NENE2 layer,
  not in this repository.
- The audit's full four-product report lives in the workspace layer; this file
  is the Deal-scoped record.

Last updated: 2026-07-11
