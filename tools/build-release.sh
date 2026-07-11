#!/usr/bin/env bash
# NeNe Deal — Tier A (shared hosting) release ZIP builder (#103, invoice
# tools/build-release.sh shape).
#
# Usage:  bash tools/build-release.sh [version]
# Output: dist/nene-deal-<version>.zip + dist/nene-deal-<version>.zip.sha256
#
# Design (nene-invoice #576 / nene-records shape):
# - The working tree's vendor is never shipped. The distribution assembles a
#   real no-dev vendor in a staging dir, pinned by composer.lock (deal resolves
#   hideyukimori/nene2 from Packagist — no path repos, no symlinks). A single
#   surviving symlink fails the build.
# - Files are copied by ALLOWLIST so dev-only tooling, secrets and build
#   byproducts can never leak into the shipment.
# - The ZIP extracts its contents at top level (WordPress style). A SHA-256
#   sidecar is emitted for integrity / mix-up detection.
#
# Build machine needs PHP 8.4 + Composer + Node 22 + zip. Server-side install
# steps are documented in docs/demo.md §5.
set -euo pipefail

VERSION="${1:-dev}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/dist"
STAGE="$DIST/build/stage"
ZIP_NAME="nene-deal-${VERSION}.zip"

echo "=== NeNe Deal Tier A release build: $VERSION ==="

rm -rf "$DIST/build"
mkdir -p "$STAGE"

# ----------------------------------------
# 1. Frontend build (Vite → served from public_html docroot)
# ----------------------------------------
echo "-> frontend build (VITE_REQUIRE_LOGIN=true)..."
(cd "$ROOT/frontend" && npm ci --silent && VITE_REQUIRE_LOGIN=true npm run build)

# ----------------------------------------
# 2. Application files (allowlist copy)
# ----------------------------------------
echo "-> copying application files (allowlist)..."
cp -r "$ROOT/src" "$STAGE/src"
cp -r "$ROOT/database" "$STAGE/database"
# public_html carries index.php, .htaccess, openapi.php, install.php and
# installer.js; the built SPA is layered in afterwards.
cp -r "$ROOT/public_html" "$STAGE/public_html"
cp "$ROOT/.env.example" "$STAGE/.env.example"
cp "$ROOT/phinx.php" "$STAGE/phinx.php"
cp "$ROOT/composer.json" "$STAGE/composer.json"
cp "$ROOT/composer.lock" "$STAGE/composer.lock"
cp "$ROOT/README.md" "$STAGE/README.md"
# public_html/openapi.php resolves ../docs/openapi/openapi.yaml.
mkdir -p "$STAGE/docs"
cp -r "$ROOT/docs/openapi" "$STAGE/docs/openapi"
# Operational / cron tools only — dev & build tooling never ships.
mkdir -p "$STAGE/tools"
cp "$ROOT/tools/sweep-demo.php" "$STAGE/tools/sweep-demo.php"
cp "$ROOT/tools/purge-trash.php" "$STAGE/tools/purge-trash.php"
cp "$ROOT/tools/seed-demo.php" "$STAGE/tools/seed-demo.php"

# var/ (empty, runtime writes: install marker, sqlite in dev setups)
mkdir -p "$STAGE/var"
touch "$STAGE/var/.gitkeep"

# ----------------------------------------
# 3. Deploy the built SPA into the docroot
# ----------------------------------------
echo "-> layering built SPA into public_html..."
cp -R "$ROOT/frontend/dist/." "$STAGE/public_html/"
rm -f "$STAGE/public_html/mockServiceWorker.js"

# ----------------------------------------
# 4. Real no-dev vendor, pinned by composer.lock
# ----------------------------------------
echo "-> composer install (no-dev, from composer.lock)..."
(cd "$STAGE" && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts --quiet)

NENE2_RESOLVED="$(cd "$STAGE" && composer show hideyukimori/nene2 2>/dev/null | awk '/^versions/ {print $NF}')"
echo "   nene2 resolved: ${NENE2_RESOLVED}"

# ----------------------------------------
# 5. Pre-ship inspection — zero symlinks, real nene2 install toolkit
# ----------------------------------------
if [ "$(find "$STAGE" -type l | wc -l)" -ne 0 ]; then
    echo "ERROR: symlinks found in the shipment:" >&2
    find "$STAGE" -type l >&2
    exit 1
fi

if [ ! -f "$STAGE/vendor/hideyukimori/nene2/src/Install/EnvironmentWriter.php" ]; then
    echo "ERROR: vendor/hideyukimori/nene2 did not resolve to a real package." >&2
    exit 1
fi

if [ ! -f "$STAGE/public_html/index.html" ]; then
    echo "ERROR: built SPA missing from public_html (frontend build failed?)." >&2
    exit 1
fi

# ----------------------------------------
# 6. ZIP (contents at top level = WordPress style) + SHA-256 sidecar
# ----------------------------------------
echo "-> zip: dist/${ZIP_NAME}..."
mkdir -p "$DIST"
rm -f "$DIST/$ZIP_NAME" "$DIST/$ZIP_NAME.sha256"
(cd "$STAGE" && zip -qr "$DIST/$ZIP_NAME" . -x "*.DS_Store" -x "__MACOSX/*" -x "*/node_modules/*")

echo "-> sha256 sidecar..."
(cd "$DIST" && sha256sum "$ZIP_NAME" > "$ZIP_NAME.sha256")

# ----------------------------------------
# 7. Size report
# ----------------------------------------
RAW_SIZE="$(du -sh "$STAGE" | cut -f1)"
ZIP_SIZE="$(du -sh "$DIST/$ZIP_NAME" | cut -f1)"
SHA256="$(cut -d' ' -f1 "$DIST/$ZIP_NAME.sha256")"

rm -rf "$DIST/build"

echo ""
echo "OK: dist/${ZIP_NAME}"
echo "  nene2:   ${NENE2_RESOLVED}"
echo "  raw:     ${RAW_SIZE}"
echo "  zip:     ${ZIP_SIZE}"
echo "  sha256:  ${SHA256}"
echo "  sidecar: dist/${ZIP_NAME}.sha256"
