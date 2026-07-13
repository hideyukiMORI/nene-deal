# NeNe Deal セキュリティ診断 — 2026-07-13

**種別:** 認可された自己/maintainer-run 診断（保守者が自リポ・隔離環境に対して実施）。
第三者ペネトレーションではない。
**対象:** `nene-deal` `main`（コミット `64c1ade` 起点）。
**手法:** 使い捨て Docker（PHP 8.4 + MySQL 8.4、strict mode = 本番同型）で**実アプリを起動**し、
2 テナント・admin/operator をシードして実弾検証。再現一式は [`harness/`](harness/)。
**Issue / ブランチ:** #121 / `sec/121-assessment-2026-07`
**本番へは一切実施していない**（`deal.ayane.co.jp` 等）。DoS・破壊操作なし。

最終回帰: `harness/attack.sh` → **PASS=38 / EXPOSED=0**。`composer check` 緑（261 tests）。

---

## 重点攻撃面と結果サマリ

| 攻撃面 | 結果 |
| --- | --- |
| テナント分離（org 越境・IDOR） | ✅ 堅牢（署名 `org` クレームが権威。ヘッダで上書き不可） |
| 認証 / JWT / サービストークン | ✅ 堅牢（alg=none・改ざん・誤鍵・dev 鍵・期限切れすべて 401） |
| 状態遷移 / ワークフロー改ざん | ✅ 堅牢（前提条件・org スコープ・RBAC で防御） |
| 数値の型混同・境界 | ⚠️→✅ float/string/負数は既に 422。**最大長/日付の欠落を是正（F1）** |
| CSV インジェクション | ✅ 堅牢（`CsvWriter` が数式セルを中和 + UTF-8 BOM） |
| セキュリティヘッダ / CORS | ✅ 堅牢（CSP 他あり、任意 Origin を反射しない）。**X-Powered-By を除去（F3）** |
| ログ / エラー経由の情報漏洩 | ✅ 堅牢（RFC 9457 problem+json、SQL/スタック/パス漏れなし）。**owner_label の越境漏洩を是正（F2）** |

確定 Finding **3 件**（Medium×1 / Low×2）。すべて本ブランチで修正・回帰済み。

---

## Findings

### F1 — Deal 入力の最大長 / 日付検証欠落で 500（Medium）

**経路:** `POST /api/v1/deals`, `PATCH /api/v1/deals/{id}`
**内容:** `CreateDealHandler` / `UpdateDealHandler` は型・必須・数値範囲は検証するが、
文字列長と日付形式を検証していなかった。攻撃者が制御可能な入力が MySQL(strict) 列に
到達し、未処理の `PDOException` → **HTTP 500** を返した。

| 入力 | 列 | 修正前 | 修正後 |
| --- | --- | --- | --- |
| `account_label` 300 文字 | `VARCHAR(255)` | 500 | **422** |
| `note` 70,000 文字 | `TEXT` (65,535B) | 500 | **422** |
| `expected_close_date` `"not-a-date"` | `DATE` | 500 | **422** |
| `expected_close_date` `"2026-13-45"` | `DATE` | 500 | **422** |

同種の書き込み系（`POST /users`・`PATCH /stages/{id}`・`PATCH /settings`）は既に
422 を返しており、Deal ハンドラのみが例外だった（`CreateStageHandler` は
`mb_strlen > 64` を検証済み — この非対称が根拠）。

**影響:** 認証済みユーザが任意に 500 を誘発可能（可用性・入力検証の不備）。
`APP_DEBUG=false` では本文は汎用文言のみで SQL/スタックの漏洩はなし。
**深刻度:** Medium。

**修正:** `DealField` に `MAX_ACCOUNT_LABEL=255` / `MAX_NOTE=5000` / `isValidDate()`
（厳格 `Y-m-d`・実在日）を追加し、両ハンドラで検証 → 422。
(`src/Deal/DealField.php`, `src/Deal/CreateDealHandler.php`, `src/Deal/UpdateDealHandler.php`)

### F2 — owner_user_id 越境と owner_label による他テナント email 漏洩（Low）

**経路:** `POST` / `PATCH` `/api/v1/deals` の `owner_user_id`
**内容:** 2 点の複合。
1. `owner_user_id` が無検証の任意文字列として受理されていた（過長値は F1 と同じく 500 も誘発）。
2. `PdoDealRepository` の owner 結合が **org スコープされていなかった**
   （`LEFT JOIN users u ON u.id = d.owner_user_id`、stage 結合は org スコープ済みなのに非対称）。

結果、org-A のユーザが org-B ユーザの ULID を `owner_user_id` に指定すると、`GET` 応答の
`owner_label` にその**他テナントユーザの email が露出**した（実測: `op-b@b.test`）。

**影響:** 越境情報開示。ただし攻撃には対象ユーザの 26 桁 ULID を事前に知る必要があり、
ULID は他テナントに一切露出しないため実効的な悪用性は低い。
**深刻度:** Low。

**修正（多層）:**
- owner/actor の users 結合を `AND u.organization_id = d.organization_id` で org スコープ化
  （既存の不正データも含め二度と漏らさない）。(`src/Deal/PdoDealRepository.php`)
- ハンドラで `owner_user_id` を ULID 形式検証（過長/不正値を 422 で弾き 500 も解消）。
  (`src/Deal/DealField.php::isValidUlid`, 両ハンドラ)

**残存（低優先・受容）:** 有効な ULID なら他 org ユーザを owner に**代入**すること自体は可能。
ただし結合スコープ化により `owner_label` は `null` となり email は漏れない。owner の org
所属を書き込み時に検証する整合性強化は将来課題として残す（本 PR ではスコープ外）。

### F3 — X-Powered-By で PHP バージョンバナー開示（Low / Informational）

**内容:** `.htaccess` の `Header always unset X-Powered-By` にもかかわらず、全 PHP 応答
（`/health`・API・401 含む）に `X-Powered-By: PHP/8.4.22` が付与されていた。mod_php SAPI
では mod_headers の unset が `expose_php` ヘッダを取り切れないため。

**影響:** バージョン特定を助ける軽微な情報開示。
**深刻度:** Low。

**修正:** フロントコントローラ `public_html/index.php` で `header_remove('X-Powered-By')`。
SAPI/ホスト（Docker・HETEML 等の共有ホスティング）に依存せず確実に除去。静的アセットは
そもそも PHP を経由せず本ヘッダを持たない。

---

## 検証済みの堅牢性（防御が効いていた項目）

- **テナント分離:** 他 org のディールへの read/patch/delete/stage-change/history/handoff は
  すべて **404**。`X-Organization-Slug` ヘッダは署名 `org` クレームを上書きできない（クレーム優先）。
- **JWT（`Nene2\Auth\LocalBearerTokenVerifier`）:** `alg` は HS256 固定で `none`/混同を拒否、
  `exp` は int 必須、署名は `hash_equals`。alg=none・payload 改ざん（旧署名）・誤鍵署名・
  公開 dev 鍵署名・期限切れはすべて **401**。
- **RBAC:** operator による stage 作成 / audit エクスポート / ユーザ一覧はすべて **403**。
- **状態遷移:** won 未満のディールの handoff は前提条件で **422**。他 org の stage ULID への
  移動は拒否。won → Invoice handoff は冪等（二重連携なし）・上流失敗時は unlinked のまま。
- **数値:** `amount_cents` は `is_int` 厳格で float `1.5` / 文字列 `"100"` / 負数 / bigint 超過
  （JSON float 化）をすべて 422。`probability_percent` は 0–100 に制限。bigint 最大値
  (`9223372036854775807`) は overflow せず受理。
- **CSV:** audit エクスポートは `Nene2\Export\CsvWriter` が `= + - @` 始まりセルを先頭
  `'` で中和し UTF-8 BOM を付与（実測で確認）。
- **ヘッダ / CORS:** CSP・X-Frame-Options・X-Content-Type-Options・Referrer-Policy・
  Permissions-Policy を付与。CORS プリフライトは任意 Origin（`evil.example`）を反射しない。
- **エラー情報漏洩:** 400/404/422/500 いずれも RFC 9457 problem+json で、`APP_DEBUG=false`
  では SQL/DSN/列名/パス/スタックトレースの漏洩なし。

---

## 未実施 / スコープ外（正直な明記）

- **フロントエンド（React SPA）診断は未実施。** 本診断は API/バックエンドに限定。XSS・
  トークン保管・オープンリダイレクト・配信ヘッダ等の SPA 面は別途 round が必要。
- **依存ライブラリの既知 CVE 走査（composer audit 等）は未実施。**
- **owner の org 所属の書き込み時検証**（F2 残存）は未実装 — 情報開示は結合スコープ化で
  封じたため低優先として据え置き。
- **レート制限・ログイン throttle の網羅検証は限定的。** ログイン throttle テーブルの存在は
  確認したが、閾値到達までの実弾ブルートフォースは DoS 回避のため未実施。
- 検証は MySQL 8.4 に対して実施。PostgreSQL/SQLite アダプタ固有の差分は未網羅
  （F1 は SQLite では列長制約が無いため再現しない点に留意）。

---

## Remediation まとめ

| Finding | 深刻度 | 状態 | 主な修正ファイル |
| --- | --- | --- | --- |
| F1 入力の最大長/日付検証欠落で 500 | Medium | ✅ 修正・回帰済 | `DealField`, `CreateDealHandler`, `UpdateDealHandler` |
| F2 owner_user_id 越境 / email 漏洩 | Low | ✅ 修正・回帰済 | `PdoDealRepository`, `DealField`, 両ハンドラ |
| F3 X-Powered-By バナー開示 | Low | ✅ 修正・回帰済 | `public_html/index.php` |

**回帰:** `docs/security/harness/attack.sh` → PASS=38 / EXPOSED=0（実アプリ・MySQL）。
**ユニット回帰:** `CreateDealHandlerTest` / `UpdateDealHandlerTest`（境界 422）、
`PdoDealRepositoryTest::test_owner_label_never_leaks_a_foreign_organization_user`。
`composer check` 緑。
