# Demo Runbook

One page to get a presentable NeNe Deal demo running, hand out credentials,
and reset it between prospects.

**Model (current):** one pre-seeded, fixed demo organization (`default`) with
shared credentials and a one-command reset. A disposable per-visitor
organization model (NENE2 `Demo` module, as used by NeNe Invoice) is planned
to replace this — the seed already takes `--org=<slug>` so that migration is
cheap.

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

No installer exists yet; the manual path is short. Build machine needs
PHP 8.4 + Composer + Node 22; the server only needs PHP 8.4 (CLI + web),
Apache `.htaccess` support, MySQL, and rsync.

```bash
# 1. Build the artifact locally (SPA + no-dev vendor + sources)
bash tools/build-heteml-artifact.sh          # → var/heteml-artifact/

# 2. Upload — keep everything except public_html ABOVE the docroot
rsync -az var/heteml-artifact/ user@host:~/apps/nene-deal/

# 3. Point the (sub)domain docroot at ~/apps/nene-deal/public_html

# 4. Configure — on the server
cp .env.example .env   # then set:
#   APP_ENV=production / APP_DEBUG=false
#   NENE2_LOCAL_JWT_SECRET=<openssl rand -hex 32>
#   DB_ADAPTER=mysql + the product-specific MySQL database (1 product = 1 DB)
#   NENE_DEAL_API_KEY=<random>            # optional, gates writes by header

# 5. Migrate + seed (CLI PHP must be 8.4)
php8.4 vendor/bin/phinx migrate -c phinx.php
NENE_DEAL_DEMO_PASSWORD=... php8.4 tools/seed-demo.php   # demo boxes only
```

Verify: `https://<domain>/health` → 200, `/` → SPA login, then a demo login.
Reset on shared hosting = re-run step 5's seed command (cron-able the same
way).

## 6. Known limitations (say them before the demo)

- **Reload logs you out** — the bearer token lives in JS memory only (by
  design, no localStorage). Log in again after a refresh.
- **One shared organization** — concurrent visitors see (and can disturb)
  each other's changes; the reset restores deals only. Disposable per-visitor
  orgs are the planned fix.
- **Admin credentials are powerful** — demo admins can edit stages and users;
  those edits survive a reset (the seed re-upserts the demo users but does
  not rebuild stages). Hand out operator credentials unless the admin pages
  are part of the tour.
- **Unauthenticated surface** is minimal by design: `/` (SPA shell),
  `/health`, `POST /api/v1/auth/login`, and the static `openapi.php` spec.
  Every other API route returns 401 without a valid token.
