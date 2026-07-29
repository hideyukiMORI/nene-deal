# Structural Consistency Audit — 2026-07-11 (Deal findings)

On 2026-07-11 the four NeNe products (invoice / clear / deal / vault) were
audited for structural consistency across six lenses: NENE2 usage,
auth/session, multi-tenancy, installer/distribution, frontend, and the demo
mechanism. This document records the **Deal-specific** summary. Every finding
below was re-verified against this repository's actual code (file:line) before
being filed.

> **This document is a dated snapshot, not live state.** Every finding and
> file:line reference records what was observed on **2026-07-11**. Remediation
> landed after that date is **not** reflected in the finding text — the findings
> are deliberately left as written so the audit stays a faithful record of that
> day. Before acting on any finding, verify it against current code. Findings
> confirmed to be resolved carry an inline **Resolved** marker and an entry in
> the [Resolution log](#resolution-log); an unmarked finding means "no
> resolution has been recorded here", **not** "still open".

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
| 7 | `.htaccess` has neither the `E=HTTP_AUTHORIZATION` re-delivery nor hardening headers (CSP/HSTS); mitigated at runtime by `AuthorizationHeaderFallback` (`public_html/index.php:26`) — **Resolved 2026-07-15, see [Resolution log](#resolution-log)** | `public_html/.htaccess` | Medium | #91 (g), #132 |
| 8 | `tools/build-heteml-artifact.sh` produces neither a zip nor a SHA-256 sidecar — no distribution integrity guarantee (invoice's `build-release.sh` is the template) | `tools/build-heteml-artifact.sh` | High | #91 (h) |
| 9 | No JST/UTC regression test pinning the sweep TTL parsing (a trap the fleet hit twice; clear/vault have the test) | `tools/sweep-demo.php:60-64`, `tests/` | Medium | #91 (i) |
| 10 | No multi-tenancy ADR — the ULID string(26) org PK (unique in the fleet) and the claim→header→sole-org resolution order are undocumented design decisions | `docs/adr/` (0001–0004 only), `database/migrations/20260530120000_create_organizations_table.php:12` | Medium | #91 (j) |
| 11 | Migration seeds well-known credentials (`operator@nene-deal.test` / `password`); the browser installer deletes them (#65) but the manual CLI-migrate path leaves them in place | `database/migrations/20260531130000_seed_default_operator.php:27-33`, `public_html/install.php:1216-1219` | Medium | #91 (k) |

## Resolution log

Remediation that landed **after** the 2026-07-11 audit date. The finding text
above is left as originally written (see the snapshot note at the top); this
section is where the current state is recorded.

### Finding 7 — `.htaccess` Authorization re-delivery and hardening headers

**Resolved.** Both halves of the finding no longer hold, and the mitigation
mechanism it described has itself been replaced:

- **Authorization re-delivery** — `public_html/.htaccess:25` now carries
  `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]`, added by
  **#101** (`2ebb104`, 2026-07-12).
- **Hardening headers** — the same commit added `Strict-Transport-Security`,
  `X-Frame-Options`, `X-Content-Type-Options`
  (`public_html/.htaccess:50-52`) and a `Content-Security-Policy`
  (`public_html/.htaccess:77`) scoped to preserve the demo seat handoff.
- **Runtime mitigation moved** — the bespoke `AuthorizationHeaderFallback` at
  `public_html/index.php:26` is gone. Its job is now done by NENE2's opt-in
  `AuthorizationHeaderFallbackMiddleware` (NENE2 #1558 / ADR 0019), enabled via
  `enableAuthorizationHeaderFallback: true` in
  `src/Http/RuntimeServiceProvider.php:378` — landed by **#130/#131**
  (`db6ccc6`). The audit's `index.php:26` reference is therefore stale.

Re-verified against `origin/main` on **2026-07-29**. Recorded in **#132**.

## Cross-product notes

- Findings 3, 5, 8 and the `Tenancy/` strength all feed NENE2 upstream
  candidates (path-scoped throttle, pagination standardization, release-build
  template, `Nene2\Tenancy`). Those are tracked at the workspace/NENE2 layer,
  not in this repository.
- The audit's full four-product report lives in the workspace layer; this file
  is the Deal-scoped record.

Findings recorded: 2026-07-11 (unchanged). Resolution log last updated: 2026-07-29.
