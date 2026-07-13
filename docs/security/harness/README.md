# Security assessment — reproduction harness

Boots the **real** NeNe Deal app (PHP 8.4 + MySQL 8.4, strict mode = production
shape) in a disposable Docker stack and seeds two tenants with admin/operator
users so tenant isolation, RBAC, state transitions, numeric boundaries, CSV
export, headers/CORS and info-leak can be exercised with live traffic.

> ⚠️ Authorized self/maintainer-run diagnostic on an isolated, self-owned stack
> only. Not a third-party pentest. Never point it at production. Secrets are
> throwaway; `down -v` destroys the volume.

## Layout

| File | Role |
| --- | --- |
| `Dockerfile` | `php:8.4-apache` + `pdo_mysql` + rewrite, docroot `public_html` |
| `docker-compose.yml` | `app` (:8119) + `db` (:3319), MySQL 8.4. Mounts the repo + `.env.app` |
| `.env.app.example` | app config template (**set `NENE2_LOCAL_JWT_SECRET`**) |
| `seed.sql` | org-a / org-b + admin/operator each + stages + deals (incl. an org-b secret) |
| `mint.php` | HS256 JWT minter for the alg / exp / signature / org-claim tests |
| `run.sh` | one-shot: up → migrate → seed → wait for `/health` |

Seed passwords are all `Passw0rd!23` (test-only, bcrypt cost 12).

## Run

```bash
cd docs/security/harness
cp .env.app.example .env.app
php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"   # → paste into NENE2_LOCAL_JWT_SECRET
./run.sh                                            # up + migrate + seed
curl -s localhost:8119/health
```

Migrations and seed run by hand (no committed MySQL schema dump; the app
provisions via phinx):

```bash
docker compose up -d --build
docker compose exec -T app php vendor/bin/phinx migrate -c phinx.php
docker compose exec -T db mysql -unene -pnene_pw nene_deal < seed.sql
```

## Log in and get a token

```bash
curl -s -X POST localhost:8119/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin-a@a.test","password":"Passw0rd!23"}'
```

## Tear down (destroys the volume)

```bash
docker compose -p nene-deal-sectest down -v
```

## Notes

- DB passwords in `docker-compose.yml` / `seed.sql` are **local throwaway**.
- `.env.app` (real secret), `tokens.env`, `*.log` are `.gitignore`d.
- Ports 8119 / 3319 are in the fixed 81** block (AGENTS.md), non-colliding with
  the dev stack (8110 / 3310).
