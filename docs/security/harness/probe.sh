#!/usr/bin/env bash
# NeNe Deal — security assessment harness (authorized, isolated instances only).
# Boots a throwaway stack, migrates + seeds, runs the attack battery, asserts,
# tears down. Ported from the nene-records probe.sh (ecosystem canonical form).
#
# ⚠️ Authorized self/maintainer-run diagnostic on a self-owned, isolated stack
# only. Never run against production (deal.ayane.co.jp / *.ayane.co.jp).
# No DoS / no destructive actions. `down -v` destroys the volume on exit.
set -uo pipefail

PROJECT=nene-deal-sectest
B="http://localhost:8119"
JWT='sectest-jwt-secret-32chars-minimum!!'   # throwaway test secret — no production meaning
PW='Passw0rd!23'
pass=0; fail=0

cd "$(dirname "$0")" || exit 1

# Fixed seed ids (see seed.sql).
ORG_A_DEAL=01KXDXRM71B5DV8Q5SE7T5C712
ORG_A_WON_DEAL=01KXDXRM71B5DV8Q5SE7T5C713
ORG_A_ADMIN=01KXDXRM71B5DV8Q5SE7T5C70Y
ORG_A_STAGE_LEAD=01KXDXRM70N9BESBY7DWB773K4
ORG_B_DEAL=01KXDXRM71B5DV8Q5SE7T5C714
ORG_B_ADMIN=01KXDXRM71B5DV8Q5SE7T5C710

cleanup() { docker compose -p "$PROJECT" down -v >/dev/null 2>&1; }
trap cleanup EXIT

check() { # name expected actual
  if [ "$2" = "$3" ]; then echo "  PASS    $1 ($3)"; pass=$((pass+1));
  else echo "  EXPOSED $1 (expected $2, got $3)"; fail=$((fail+1)); fi
}
code() { curl -s -o /dev/null -w '%{http_code}' "$@"; }
tok() { curl -s -X POST "$B/api/v1/auth/login" -H 'Content-Type: application/json' \
  -d "{\"email\":\"$1\",\"password\":\"$PW\"}" \
  | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["token"]??"ERR";'; }

echo "== boot isolated stack (MySQL 8.4 strict, APP_DEBUG=false) =="
sed "s|^NENE2_LOCAL_JWT_SECRET=.*|NENE2_LOCAL_JWT_SECRET=${JWT}|" .env.app.example > .env.app
docker compose -p "$PROJECT" up -d --build >/dev/null 2>&1
for _ in $(seq 1 40); do [ "$(code "$B/health")" = "200" ] && break; sleep 3; done
docker compose -p "$PROJECT" exec -T app php vendor/bin/phinx migrate -c phinx.php >/dev/null 2>&1
docker compose -p "$PROJECT" exec -T db mysql -unene -pnene_pw nene_deal < seed.sql >/dev/null 2>&1

TA=$(tok admin-a@a.test)   # org A admin
TB=$(tok admin-b@b.test)   # org B admin
[ "$TA" = ERR ] || [ "$TB" = ERR ] && { echo "login failed — aborting"; exit 1; }

echo "== F-01 analog: every admin GET must require auth (401) =="
for p in /api/v1/auth/me /api/v1/board /api/v1/deals "/api/v1/deals/$ORG_A_DEAL" \
         "/api/v1/deals/$ORG_A_DEAL/history" /api/v1/forecast /api/v1/settings \
         /api/v1/stages /api/v1/users "/api/v1/users/$ORG_A_ADMIN" \
         "/api/v1/audit/export?from=2026-01-01&to=2026-12-31"; do
  check "unauth GET $p" 401 "$(code "$B$p")"
done
check "unauth POST /api/v1/deals (mutation)" 401 "$(code -X POST "$B/api/v1/deals" -H 'Content-Type: application/json' -d '{}')"
check "unauth PATCH /api/v1/settings"        401 "$(code -X PATCH "$B/api/v1/settings" -H 'Content-Type: application/json' -d '{}')"
check "GET /health stays open"               200 "$(code "$B/health")"
check "GET / (SPA shell) stays open"         200 "$(code "$B/")"

echo "== F-02 analog: org-B JWT replay against org-A resources must be blocked (404) =="
AH=(-H "Authorization: Bearer $TB")   # org-B admin token, replayed at org-A ids
check "B->A GET deal"           404 "$(code "${AH[@]}" "$B/api/v1/deals/$ORG_A_DEAL")"
check "B->A GET deal history"   404 "$(code "${AH[@]}" "$B/api/v1/deals/$ORG_A_DEAL/history")"
check "B->A PATCH deal"         404 "$(code "${AH[@]}" -X PATCH "$B/api/v1/deals/$ORG_A_DEAL" -H 'Content-Type: application/json' -d '{"amount_cents":1}')"
check "B->A DELETE deal"        404 "$(code "${AH[@]}" -X DELETE "$B/api/v1/deals/$ORG_A_DEAL")"
check "B->A stage-change"       404 "$(code "${AH[@]}" -X POST "$B/api/v1/deals/$ORG_A_DEAL/stage-change" -H 'Content-Type: application/json' -d '{"to_stage_id":"lead"}')"
check "B->A restore"            404 "$(code "${AH[@]}" -X POST "$B/api/v1/deals/$ORG_A_DEAL/restore")"
check "B->A invoice-handoff"    404 "$(code "${AH[@]}" -X POST "$B/api/v1/deals/$ORG_A_WON_DEAL/invoice-handoff")"
check "B->A GET user"           404 "$(code "${AH[@]}" "$B/api/v1/users/$ORG_A_ADMIN")"
check "B->A PATCH user"         404 "$(code "${AH[@]}" -X PATCH "$B/api/v1/users/$ORG_A_ADMIN" -H 'Content-Type: application/json' -d '{"role":"admin"}')"
check "B->A DELETE user"        404 "$(code "${AH[@]}" -X DELETE "$B/api/v1/users/$ORG_A_ADMIN")"
check "B->A PATCH stage"        404 "$(code "${AH[@]}" -X PATCH "$B/api/v1/stages/$ORG_A_STAGE_LEAD" -H 'Content-Type: application/json' -d '{"label":"x"}')"
# List endpoints must never spill the other org's rows.
curl -s "${AH[@]}" "$B/api/v1/deals" | grep -q 'Acme Corp (Org A)' && { echo "  EXPOSED B's deal list leaks org-A"; fail=$((fail+1)); } || { echo "  PASS    B's deal list excludes org-A"; pass=$((pass+1)); }
curl -s "${AH[@]}" "$B/api/v1/users" | grep -q 'admin-a@a.test' && { echo "  EXPOSED B's user list leaks org-A"; fail=$((fail+1)); } || { echo "  PASS    B's user list excludes org-A"; pass=$((pass+1)); }
# Positive controls: B reaching its OWN resources still works.
check "B->B GET own secret deal" 200 "$(code "${AH[@]}" "$B/api/v1/deals/$ORG_B_DEAL")"
check "B->B GET own user"        200 "$(code "${AH[@]}" "$B/api/v1/users/$ORG_B_ADMIN")"
check "X-Organization-Slug cannot override signed org claim" 404 "$(code "${AH[@]}" -H 'X-Organization-Slug: org-a' "$B/api/v1/deals/$ORG_A_DEAL")"

echo "== JWT / headers spot-checks =="
NONE="$(printf '{"typ":"JWT","alg":"none"}' | base64 -w0 | tr '+/' '-_' | tr -d '=').$(printf '{"sub":"a","role":"admin","org":"x","exp":9999999999}' | base64 -w0 | tr '+/' '-_' | tr -d '=')."
check "JWT alg:none forged"      401 "$(code -H "Authorization: Bearer $NONE" "$B/api/v1/deals")"
check "malformed bearer"         401 "$(code -H 'Authorization: Bearer not.a.jwt' "$B/api/v1/deals")"
hdr=$(curl -s -D - -o /dev/null "$B/health")
echo "$hdr" | grep -qi '^X-Powered-By:' && { echo "  EXPOSED X-Powered-By present"; fail=$((fail+1)); } || { echo "  PASS    X-Powered-By absent"; pass=$((pass+1)); }

echo
echo "== RESULT: PASS=$pass  EXPOSED=$fail =="
[ "$fail" -eq 0 ]
