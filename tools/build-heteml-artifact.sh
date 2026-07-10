#!/usr/bin/env bash
# Builds a shared-hosting deploy artifact (HETEML-style: no node/npm on the
# server, PHP 8.4 CLI + Apache with .htaccess available) on a full-toolchain
# machine. The result is a self-contained tree ready to rsync:
#
#   var/heteml-artifact/
#     public_html/   ← point the (sub)domain docroot HERE (SPA + front controller)
#     vendor/        ← no-dev, stays above the docroot (not web-reachable)
#     src/ database/ tools/ docs/openapi/ phinx.php composer.json composer.lock
#     .env.example   ← copy to .env on the server and fill in
#
# Usage:
#   bash tools/build-heteml-artifact.sh [output-dir]   # default: var/heteml-artifact
#
# Server-side install steps are documented in docs/demo.md.
set -euo pipefail

cd "$(dirname "$0")/.."
OUT=${1:-var/heteml-artifact}

echo "==> Building SPA (VITE_REQUIRE_LOGIN=true)"
(cd frontend && npm ci --silent && VITE_REQUIRE_LOGIN=true npm run build)

echo "==> Assembling artifact in ${OUT}"
rm -rf "$OUT"
mkdir -p "$OUT"

cp -R src database tools phinx.php composer.json composer.lock "$OUT"/
# docs/openapi keeps its docs/ prefix (public_html/openapi.php resolves
# ../docs/openapi/openapi.yaml relative to the project root).
mkdir -p "$OUT/docs"
cp -R docs/openapi "$OUT/docs/openapi"
cp -R public_html "$OUT/public_html"
cp .env.example "$OUT/.env.example"

echo "==> Installing vendor (no-dev)"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    --working-dir="$OUT" --quiet

echo "==> Deploying SPA into public_html"
cp -R frontend/dist/. "$OUT/public_html/"
rm -f "$OUT/public_html/mockServiceWorker.js"

echo
echo "Artifact ready: ${OUT}"
echo "Next (server): rsync the tree, point the docroot at public_html/,"
echo "copy .env.example to .env and fill it in, then run:"
echo "  php8.4 vendor/bin/phinx migrate -c phinx.php"
echo "  NENE_DEAL_DEMO_PASSWORD=... php8.4 tools/seed-demo.php   # demo boxes only"
echo "See docs/demo.md for the full runbook."
