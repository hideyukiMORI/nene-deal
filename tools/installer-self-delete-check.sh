#!/usr/bin/env bash
#
# nene-deal#224 やること2・裁定 (c): 「marker が在れば installer は自分を消す」を実物で確かめる。
#
# 🔴 CI には配線しない。ローカルの Docker 環境と、**PHP から書ける docroot** が要る
#    （目視束のハーネスと同じ扱い。動かせない検査を `check` に入れると、緑が意味を失う）。
#
#   DEAL_INSTALLER_CHECK_BASE   既定 http://localhost:8110
#
# ## なぜ二方向の対照が要るか
#
# 「消えた」だけを見ると、**常に消している実装**と区別できない。ガードを通らない経路でも
# 消えるなら、それは初回インストールを不可能にする致命的な退行だが、A だけの検査は緑になる。
# ⇒ B で `isBlocked()` の枝を `false` へ変異させ、**消えないこと**まで見る。
#
# ## 既知の環境依存（黙って失敗する側）
#
# docroot が root 所有 + PHP が www-data だと `unlink(): Permission denied` になり、
# **応答は変わらないまま消えない**（2026-08-26 実測）。このスクリプトは A で落ちるが、
# 本番では unlink の失敗が**応答から見えない**。それを見張るのは
# リリース手順書の `test ! -e public_html/install.php` のほう。
# ローカルで A を通すには一時的に `chmod o+w public_html`（終わったら戻す）。
set -u
cd "$(dirname "$0")/.."

DOC=public_html
BASE="${DEAL_INSTALLER_CHECK_BASE:-http://localhost:8110}"
MARKER=var/.installed
A="$DOC/install-selftest-a.php"
B="$DOC/install-selftest-b.php"
fail=0
MADE_MARKER=

cleanup() {
  rm -f "$A" "$B"
  [ -n "$MADE_MARKER" ] && rm -f "$MARKER"
  return 0
}
trap cleanup EXIT

if [ ! -e "$MARKER" ]; then
  mkdir -p var && date -Iseconds > "$MARKER"
  MADE_MARKER=1
  echo "（marker が無いので一時作成した。終了時に消す）"
fi

# `instance` だけが要求パスを映すので、そこを伏せて突合する。
norm() { sed -E 's#"instance": *"[^"]*"#"instance":"X"#'; }

echo "=== A: guard が塞ぐとき — 自分を消し、存在しないパスとして応答する ==="
cp "$DOC/install.php" "$A"
a_body=$(curl -s "$BASE/$(basename "$A")")
a_code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$(basename "$A")")

if [ -e "$A" ]; then
  echo "  ❌ 消えていない（docroot が PHP から書けない可能性・上のコメント参照）"
  fail=1
else
  echo "  ✅ 自分を消した"
fi

ref=$(curl -s "$BASE/definitely-not-a-real-path")
if [ "$(norm <<<"$a_body")" = "$(norm <<<"$ref")" ] && [ "$a_code" = "401" ]; then
  echo "  ✅ 応答が存在しないパスと同一（$a_code・instance を除いて byte 一致）"
else
  # 🔴 「200 でないこと」では検査にならない。ブロック画面自身が 403 を返すので、
  #    削除前でも通ってしまう（#224 の当初のクローズ条件がこの誤りだった）。
  echo "  ❌ 応答が違う: code=$a_code（期待 401 + 未知パスと同一の problem document）"
  diff <(norm <<<"$a_body") <(norm <<<"$ref") | head -8
  fail=1
fi

echo
echo "=== B: 対照 — guard の枝を false へ変異させたら、消えないこと ==="
sed 's/if ($reinstallGuard->isBlocked()) {/if (false) {/' "$DOC/install.php" > "$B"
if ! grep -q 'if (false) {' "$B"; then
  echo "  ❌ 変異が当たっていない＝この対照は何も検査していない（install.php の該当行が変わった？）"
  fail=1
else
  curl -s -o /dev/null "$BASE/$(basename "$B")"
  if [ -e "$B" ]; then
    echo "  ✅ 消えない（削除は guard の枝の中でだけ起きている）"
  else
    echo "  ❌ guard を通らなくても消えた — **常に消す実装**になっている（初回設置が不可能）"
    fail=1
  fi
fi

echo
if [ "$fail" -eq 0 ]; then echo "=== 合格 ==="; else echo "=== 🔴 不合格 ==="; fi
exit "$fail"
