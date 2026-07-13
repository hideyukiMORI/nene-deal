#!/usr/bin/env bash
# Authorized self/maintainer-run assessment battery (#121). Asserts each probe's
# expected outcome and prints PASS / EXPOSED. Non-destructive: creates a handful
# of rows in the disposable stack only. Never point at production.
set -uo pipefail
cd "$(dirname "$0")"
source ./tokens.env
SEC=$(grep '^NENE2_LOCAL_JWT_SECRET=' .env.app | cut -d= -f2)
PASS=0; EXPOSED=0

st() { # METHOD PATH TOKEN [json] [extra-header]
  local m=$1 p=$2 t=$3 body=${4:-} xh=${5:-}
  local args=(-s -o /tmp/o -w '%{http_code}' -X "$m" "$B$p")
  [[ -n $t  ]] && args+=(-H "Authorization: Bearer $t")
  [[ -n $xh ]] && args+=(-H "$xh")
  [[ -n $body ]] && args+=(-H 'Content-Type: application/json' -d "$body")
  curl "${args[@]}"
}
mint() { php mint.php "$1" "$2" "${3:-{\"typ\":\"JWT\",\"alg\":\"HS256\"}}"; }
# want <label> <got> <expected...>
want() {
  local label=$1 got=$2; shift 2
  for e in "$@"; do if [[ $got == "$e" ]]; then printf 'PASS    %-3s  %s\n' "$got" "$label"; PASS=$((PASS+1)); return; fi; done
  printf 'EXPOSED %-3s  %s (wanted %s)\n' "$got" "$label" "$*"; EXPOSED=$((EXPOSED+1))
}
grepwant() { # label file needle expect(present|absent)
  if grep -q "$3" "$2"; then [[ $4 == present ]] && { echo "PASS         $1"; PASS=$((PASS+1)); } || { echo "EXPOSED      $1"; EXPOSED=$((EXPOSED+1)); }
  else [[ $4 == absent ]] && { echo "PASS         $1"; PASS=$((PASS+1)); } || { echo "EXPOSED      $1"; EXPOSED=$((EXPOSED+1)); }; fi
}

echo "############ TENANT ISOLATION ############"
want "A reads B's secret deal by id"           "$(st GET  /api/v1/deals/$DEAL_B_SECRET "$TA_ADMIN")" 404
want "A + X-Organization-Slug:org-b (claim wins)" "$(st GET /api/v1/deals/$DEAL_B_SECRET "$TA_ADMIN" '' 'X-Organization-Slug: org-b')" 404
st GET /api/v1/deals "$TA_ADMIN" >/dev/null; grepwant "A's deal list excludes org-b secret" /tmp/o CONFIDENTIAL absent
want "A patches B's deal"                       "$(st PATCH /api/v1/deals/$DEAL_B_SECRET "$TA_ADMIN" '{"amount_cents":1}')" 404
want "A moves B's deal stage"                   "$(st POST /api/v1/deals/$DEAL_B_SECRET/stage-change "$TA_ADMIN" '{"to_stage_id":"lead"}')" 404
want "A deletes B's deal"                       "$(st DELETE /api/v1/deals/$DEAL_B_SECRET "$TA_ADMIN")" 404
want "A reads B's deal history"                 "$(st GET /api/v1/deals/$DEAL_B_SECRET/history "$TA_ADMIN")" 404
want "A hands off B's deal"                     "$(st POST /api/v1/deals/$DEAL_B_SECRET/invoice-handoff "$TA_ADMIN")" 404

echo "############ AUTH / JWT ############"
want "no token on protected read"              "$(st GET /api/v1/deals '')" 401
want "alg=none forged admin"                   "$(st GET /api/v1/deals "$(mint '{"sub":"x","role":"admin","org":"'$ORG_B'","exp":9999999999}' '' '{"typ":"JWT","alg":"none"}')")" 401
HDR=$(echo "$TA_ADMIN"|cut -d. -f1); SIG=$(echo "$TA_ADMIN"|cut -d. -f3)
FP=$(php mint.php '{"sub":"x","role":"admin","org":"'$ORG_B'","exp":9999999999}' 'x'|cut -d. -f2)
want "tampered org claim, original sig"         "$(st GET /api/v1/deals/$DEAL_B_SECRET "$HDR.$FP.$SIG")" 401
want "signed with wrong secret"                 "$(st GET /api/v1/deals "$(mint '{"sub":"x","role":"admin","org":"'$ORG_B'","exp":9999999999}' 'wrong')")" 401
want "signed with public dev secret"            "$(st GET /api/v1/deals "$(mint '{"sub":"x","role":"admin","org":"'$ORG_B'","exp":9999999999}' 'nene-deal-dev-secret')")" 401
want "expired token, correct secret"            "$(st GET /api/v1/deals "$(mint '{"sub":"x","role":"admin","org":"'$ORG_B'","exp":1000000000}' "$SEC")")" 401

echo "############ RBAC / STATE TRANSITION ############"
want "operator creates stage (admin only)"     "$(st POST /api/v1/stages "$TA_OP" '{"label":"x","sort_order":9}')" 403
want "operator exports audit (admin only)"     "$(st GET '/api/v1/audit/export?from=2026-01-01&to=2026-12-31' "$TA_OP")" 403
want "operator lists users (admin only)"       "$(st GET /api/v1/users "$TA_OP")" 403
# Fresh lead-stage deal so the precondition (not the stack's mutable state) is
# what is exercised.
FRESH=$(curl -s -X POST "$B/api/v1/deals" -H "Authorization: Bearer $TA_ADMIN" -H 'Content-Type: application/json' -d '{"account_label":"handoff-precond","amount_cents":1000,"stage_id":"lead"}' | php -r '$j=json_decode(stream_get_contents(STDIN),true);echo $j["id"]??"ERR";')
want "handoff a non-won deal (precondition)"   "$(st POST /api/v1/deals/$FRESH/invoice-handoff "$TA_ADMIN")" 422
want "move A-deal into B's stage ULID"         "$(st POST /api/v1/deals/$DEAL_A1/stage-change "$TA_ADMIN" '{"to_stage_id":"'$STG_B_WON'"}')" 422 404

echo "############ NUMERIC BOUNDARIES ############"
mk() { st POST /api/v1/deals "$TA_ADMIN" "$1"; }
want "amount float 1.5"        "$(mk '{"account_label":"x","amount_cents":1.5,"stage_id":"lead"}')" 422
want "amount string \"100\""    "$(mk '{"account_label":"x","amount_cents":"100","stage_id":"lead"}')" 422
want "amount negative"          "$(mk '{"account_label":"x","amount_cents":-5,"stage_id":"lead"}')" 422
want "amount > bigint (float)"  "$(mk '{"account_label":"x","amount_cents":99999999999999999999,"stage_id":"lead"}')" 422
want "amount = bigint max"      "$(mk '{"account_label":"x","amount_cents":9223372036854775807,"stage_id":"lead"}')" 201
want "probability 101"          "$(mk '{"account_label":"x","amount_cents":1,"probability_percent":101,"stage_id":"lead"}')" 422
want "account_label 300ch >255" "$(mk '{"account_label":"'$(printf 'A%.0s' {1..300})'","amount_cents":1,"stage_id":"lead"}')" 422
want "note 70000ch >TEXT"       "$(mk '{"account_label":"x","amount_cents":1,"note":"'$(printf 'B%.0s' {1..70000})'","stage_id":"lead"}')" 422
want "expected_close_date bad"  "$(mk '{"account_label":"x","amount_cents":1,"stage_id":"lead","expected_close_date":"not-a-date"}')" 422
want "expected_close_date 13-45" "$(mk '{"account_label":"x","amount_cents":1,"stage_id":"lead","expected_close_date":"2026-13-45"}')" 422
want "owner_user_id 300ch"      "$(mk '{"account_label":"x","amount_cents":1,"stage_id":"lead","owner_user_id":"'$(printf 'x%.0s' {1..300})'"}')" 422

echo "############ OWNER CROSS-TENANT LEAK ############"
NID=$(curl -s -X POST "$B/api/v1/deals" -H "Authorization: Bearer $TA_ADMIN" -H 'Content-Type: application/json' -d '{"account_label":"probe","amount_cents":1,"stage_id":"lead","owner_user_id":"'$UBOP'"}' | php -r '$j=json_decode(stream_get_contents(STDIN),true);echo $j["id"]??"ERR";')
st GET /api/v1/deals/$NID "$TA_ADMIN" >/dev/null
grepwant "org-B owner id does not leak op-b@b.test as owner_label" /tmp/o 'op-b@b.test' absent

echo "############ CSV FORMULA INJECTION ############"
DID=$(curl -s -X POST "$B/api/v1/deals" -H "Authorization: Bearer $TA_ADMIN" -H 'Content-Type: application/json' -d '{"account_label":"=cmd|calc","amount_cents":1,"stage_id":"lead"}' | php -r '$j=json_decode(stream_get_contents(STDIN),true);echo $j["id"]??"ERR";')
curl -s -o /dev/null -X PATCH "$B/api/v1/deals/$DID" -H "Authorization: Bearer $TA_ADMIN" -H 'Content-Type: application/json' -d '{"account_label":"@SUM(1)*x"}'
curl -s "$B/api/v1/audit/export?from=2026-01-01&to=2026-12-31" -H "Authorization: Bearer $TA_ADMIN" > /tmp/o
grepwant "formula cell neutralised with leading quote" /tmp/o "'=cmd" present

echo "############ HEADERS / CORS / INFO LEAK ############"
curl -s -D - -o /dev/null "$B/api/v1/deals" -H "Authorization: Bearer $TA_ADMIN" > /tmp/o
grepwant "Content-Security-Policy present"       /tmp/o 'Content-Security-Policy' present
grepwant "X-Content-Type-Options present"        /tmp/o 'X-Content-Type-Options' present
grepwant "X-Frame-Options present"               /tmp/o 'X-Frame-Options' present
grepwant "X-Powered-By banner removed"           /tmp/o 'X-Powered-By' absent
curl -s -D - -o /dev/null -X OPTIONS "$B/api/v1/deals" -H 'Origin: https://evil.example' -H 'Access-Control-Request-Method: GET' > /tmp/o
grepwant "CORS does not reflect evil origin"     /tmp/o 'evil.example' absent
st POST /api/v1/deals "$TA_ADMIN" '{"account_label":"'"$(printf 'A%.0s' {1..300})"'","amount_cents":1,"stage_id":"lead"}' >/dev/null
grepwant "422 body carries no SQL/stack/path"    /tmp/o 'SQLSTATE' absent

echo
echo "================= RESULT: PASS=$PASS  EXPOSED=$EXPOSED ================="
rm -f /tmp/o
[[ $EXPOSED -eq 0 ]]
