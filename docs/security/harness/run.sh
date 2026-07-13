#!/usr/bin/env bash
# One-shot boot for the disposable assessment stack: up -> migrate -> seed ->
# wait for /health. Idempotent enough to re-run after `down -v`.
set -euo pipefail
cd "$(dirname "$0")"

if [[ ! -f .env.app ]]; then
  echo "Missing .env.app — cp .env.app.example .env.app and set NENE2_LOCAL_JWT_SECRET" >&2
  exit 1
fi

docker compose up -d --build
echo "waiting for db healthcheck..."
docker compose exec -T app php vendor/bin/phinx migrate -c phinx.php
docker compose exec -T db mysql -unene -pnene_pw nene_deal < seed.sql
echo "seeded. health:"
for _ in $(seq 1 20); do
  if curl -fsS localhost:8119/health >/dev/null 2>&1; then curl -s localhost:8119/health; echo; exit 0; fi
  sleep 1
done
echo "health check did not come up" >&2
exit 1
