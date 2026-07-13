#!/usr/bin/env bash
# Log in the four seeded users and write tokens.env (gitignored) for attack.sh.
# The deal/stage/user ids are the fixed ULIDs from seed.sql.
set -euo pipefail
cd "$(dirname "$0")"
B=localhost:8119
tok() { curl -s -X POST "$B/api/v1/auth/login" -H 'Content-Type: application/json' \
  -d "{\"email\":\"$1\",\"password\":\"Passw0rd!23\"}" \
  | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["token"]??"ERR";'; }

cat > tokens.env <<EOF
export B=$B
export TA_ADMIN=$(tok admin-a@a.test)
export TA_OP=$(tok op-a@a.test)
export TB_ADMIN=$(tok admin-b@b.test)
export TB_OP=$(tok op-b@b.test)
export DEAL_A1=01KXDXRM71B5DV8Q5SE7T5C712
export DEAL_A_WON=01KXDXRM71B5DV8Q5SE7T5C713
export DEAL_B_SECRET=01KXDXRM71B5DV8Q5SE7T5C714
export STG_B_WON=01KXDXRM71B5DV8Q5SE7T5C70X
export ORG_B=01KXDXRM70N9BESBY7DWB773K3
export UBOP=01KXDXRM71B5DV8Q5SE7T5C711
EOF
echo "wrote tokens.env"
