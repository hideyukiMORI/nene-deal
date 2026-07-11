# Demo Runbook

One page to get a presentable NeNe Deal demo running, hand out credentials,
and reset it between prospects.

**Two coexisting models** (#69):

1. **Disposable per-visitor demo** — `GET /demo/standard` provisions a
   throwaway `demo-…` organization, seeds the presentation pipeline and lands
   the visitor on the kanban with no login. The link IS the demo; "reset"
   means clicking it again. See section 0.
2. **Fixed demo organization** (`default`) — pre-seeded, shared credentials,
   one-command reset (`tools/seed-demo.php` / its cron). Use for guided demos
   where handing out a login is part of the tour. Sections 1–4.

Both draw the same dataset from `NeneDeal\Demo\DemoPipelineFixture`.

## 0. Disposable demo (`/demo/{template}`)

NENE2 `Nene2\Demo` consumer (framework v1.10.0). Strictly opt-in: with
`DEMO_MODE` unset the route answers a plain 404, so the wiring ships dormant.

```bash
# .env — enable per deployment (only 1/true/yes enable it; fail-close)
DEMO_MODE=1
# DEMO_SLUG_PREFIX=demo-   DEMO_TTL_HOURS=3   DEMO_MAX_ORGS=200
```

- **Flow:** provision org + default stages + throwaway admin → seed 15
  funnel-shaped deals (3 owners) → seat page parks a normal bearer token in
  `sessionStorage` and `location.replace('/')`; the SPA boot imports it
  one-shot. Token TTL = 1 h login TTL, no refresh; reload or expiry simply
  means revisiting the URL for a brand-new org.
- **Tenancy:** authenticated requests resolve their organization from the
  signed `org` claim, so a demo session is locked to its own org and the
  fixed org keeps working with organizations plural.
- **Protection:** per-IP throttle 30 starts/h (file-backed under
  `var/rate-limits/` — shared hosting has no cross-process memory) and an
  instance-wide ceiling of 200 demo orgs. Browser-facing errors are branded
  HTML (429 with live countdown); API-shaped clients get RFC 9457 JSON.
- **Sweep (cron, hourly):** destroys `demo-…` orgs older than
  `DEMO_TTL_HOURS` and overflow beyond `DEMO_MAX_ORGS`; the fixed org and
  real tenants are never touched. Idempotent.

```cron
0 * * * * cd /path/to/nene-deal && php8.4 tools/sweep-demo.php >> var/log/sweep-demo.log 2>&1
```

## 1. Start (production-shaped container)

Requirements: Docker. Secrets are required — the stack refuses to start
without them (fail-close).

```bash
export NENE2_LOCAL_JWT_SECRET=$(openssl rand -hex 32)
export NENE_DEAL_DB_PASSWORD=$(openssl rand -hex 16)
export NENE_DEAL_DB_ROOT_PASSWORD=$(openssl rand -hex 16)
export NENE_DEAL_DEMO_PASSWORD='choose-the-demo-password'

docker compose -f compose.prod.yaml up -d --build   # migrates on boot
docker compose -f compose.prod.yaml exec app php tools/seed-demo.php
```

- URL: **http://localhost:8111** (local verification; behind TLS when hosted)
- The image serves the built SPA and the API from the same origin
  (`public_html/` docroot, see `public_html/.htaccess`); `APP_DEBUG=false`,
  operator JWT enforced on every API route except `/`, `/health` and login.
- This is separate from the dev stack (`compose.yaml`, port 8110); both can
  run side by side.

## 2. Demo credentials

Seeded (upserted) by `tools/seed-demo.php`; shared password =
`NENE_DEAL_DEMO_PASSWORD` (defaults to `deal-demo` in local/test only —
production refuses the default).

| Login | Role | Use for |
| --- | --- | --- |
| `demo-admin@nene-deal.test` | admin | full tour incl. Users / Stages / Audit / Settings |
| `sato@nene-deal.test` | operator | day-to-day operator view |
| `takahashi@nene-deal.test` | operator | second owner on the board |

## 3. What to show

- **Board** (`/`): 15 deals funnel-shaped across the six stages, realistic
  Japanese company names, amounts and owners; drag a card to move stage.
- **Forecast** (board header): weighted monthly forecast with real substance
  this month, next month and the month after (seed dates are relative to the
  run date, so it always looks live).
- **Deal detail** (`/deals/{id}`): stage timeline (created → moves) built the
  same way the app writes it.
- **Won → Invoice handoff**: show the action on a won deal and explain the
  HTTP handoff to NeNe Invoice — the demo box is intentionally not connected
  (`NENE_DEAL_INVOICE_BASE_URL` empty).

## 4. Reset

Re-running the seed **is** the reset — it wipes the organization's deals
(and their activity rows) and reseeds; users, stages and settings stay:

```bash
docker compose -f compose.prod.yaml exec app php tools/seed-demo.php
```

Cron example (nightly, 03:00):

```cron
0 3 * * * docker compose -f /path/to/nene-deal/compose.prod.yaml exec -T app php tools/seed-demo.php
```

## 5. Shared hosting (HETEML-style) install

Build machine needs PHP 8.4 + Composer + Node 22 + zip; the server only needs
PHP 8.4 (CLI + web), Apache `.htaccess` support, MySQL, and rsync (or unzip).

```bash
# 1. Build the release ZIP locally (#103: allowlist staging + no-dev vendor
#    pinned by composer.lock + zero-symlink check + SHA-256 sidecar)
bash tools/build-release.sh 1.0.0
#   → dist/nene-deal-1.0.0.zip + dist/nene-deal-1.0.0.zip.sha256

# 2. Verify integrity, then upload — keep everything except public_html
#    ABOVE the docroot
(cd dist && sha256sum -c nene-deal-1.0.0.zip.sha256)
unzip -q dist/nene-deal-1.0.0.zip -d /tmp/nene-deal-release
rsync -az /tmp/nene-deal-release/ user@host:~/apps/nene-deal/
#   (or upload the ZIP and unzip on the server — contents are top level)

# 3. Point the (sub)domain docroot at ~/apps/nene-deal/public_html
```

**4. Install in the browser (#65)** — open `https://<domain>/install.php` and
follow the wizard (server requirements → DB connection test → organization
name + admin account). The installer writes `.env` atomically via the NENE2
`EnvironmentWriter` (0640 fail-closed, values escaped, JWT secret
auto-generated; the admin password is handed over in memory only, never
persisted), applies the phinx migrations in-process
(`Nene2\Install\DatabaseSchemaApplier` — no CLI needed), **deletes the
dev-seeded `operator@nene-deal.test` account** and creates the real admin.
On success it self-deletes; a `var/.installed` marker plus a DB probe
(`ReInstallationGuard`) refuse any later re-run. If a stray copy survives,
delete `public_html/install.php` manually.

Manual alternative (no browser): `cp .env.example .env`, set
`APP_ENV=production` / `APP_DEBUG=false` / `NENE2_LOCAL_JWT_SECRET` /
`DB_*`, then `php8.4 vendor/bin/phinx migrate -c phinx.php`. The migrations
seed `operator@nene-deal.test` / `password` (admin role) — change or delete
that account immediately on any reachable box.

```bash
# 5. Demo boxes only: seed the presentation pipeline (CLI PHP must be 8.4)
NENE_DEAL_DEMO_PASSWORD=... php8.4 tools/seed-demo.php
```

Verify: `https://<domain>/health` → 200, `/` → SPA login, then a demo login.
Reset on shared hosting = re-run step 5's seed command (cron-able the same
way).

## 6. Known limitations (say them before the demo)

- **Reload logs you out** — the bearer token lives in JS memory only (by
  design, no localStorage). Log in again after a refresh.
- **One shared organization** (fixed-org model only) — concurrent visitors
  see (and can disturb) each other's changes; the reset restores deals only.
  Prefer the disposable demo link (section 0) for unattended prospects.
- **Admin credentials are powerful** — demo admins can edit stages and users;
  those edits survive a reset (the seed re-upserts the demo users but does
  not rebuild stages). Hand out operator credentials unless the admin pages
  are part of the tour.
- **Unauthenticated surface** is minimal by design: `/` (SPA shell),
  `/health`, `POST /api/v1/auth/login`, and the static `openapi.php` spec.
  Every other API route returns 401 without a valid token.
