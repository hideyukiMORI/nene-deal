# NeNe Deal セキュリティ診断（red-team ラウンド）— 2026-07-14

**種別:** 認可された自己/maintainer-run 診断（第三者 pentest ではない）。
**対象:** `nene-deal` `main`（`04c3b9d` = #122 マージ後）。
**手法:** 使い捨て Docker（PHP 8.4 + **MySQL 8.4 strict mode = 本番同型**、`APP_DEBUG=false`）で
実アプリを起動し、2 テナントをシードして実弾。ハーネスは [`harness/probe.sh`](harness/probe.sh)
（nene-records の probe.sh 様式を移植：boot→migrate→seed→battery→assert→`down -v`）。
**Issue / ブランチ:** #123 / `sec/123-assessment-2026-07`
**本番不使用**（`deal.ayane.co.jp` 等）。DoS/破壊なし。

## このラウンドの狙い

先行の全面診断（[2026-07-13](2026-07-13-assessment.md) / #121 / PR #122）に加え、
**nene-records で実在した2つの型が nene-deal に同型で存在しないか**を最優先で live 実証する。

| nene-records の実在 finding | nene-deal での確認 |
| --- | --- |
| **F-01 Critical** — 未認証で管理 API GET が読めた（31 GET ルート・webhook 署名鍵/下書き/全 export 露出） | **未認証ルート行列**で全 GET を検証 |
| **F-02 Medium** — JWT replay で越境読み取り（capability 未マップのルートで org チェック欠落） | **JWT-replay/org-binding 行列**で全 org スコープルートを検証 |

## 結果サマリ

**`probe.sh` → PASS=34 / EXPOSED=0。新規 Finding なし。** 両クラスとも nene-deal には
**アーキテクチャ上存在しない**ことを live で確認した。

### F-01 同型（未認証 admin GET）— 存在しない ✅

**アーキテクチャ根拠:** nene-records の `AdminApiAuthMiddleware` は「GET は保護対象外（プレフィクス
未登録なら GET が漏れる）」設計だった。nene-deal の `Nene2\Auth\BearerTokenMiddleware` は
**blocklist**（`excludedPaths` の 4 パス＝`/`・`/health`・`/api/v1/auth/login`・`/demo/standard`
**以外の全メソッド・全ルートを保護**）。新規ルートは既定で保護され、GET も HEAD も fail-closed。

**live 実証（トークン無し → すべて 401）:**

```
GET /api/v1/auth/me            → 401    GET /api/v1/users               → 401
GET /api/v1/board              → 401    GET /api/v1/users/{id}          → 401
GET /api/v1/deals              → 401    GET /api/v1/audit/export        → 401   ← 最も機微(監査証跡)
GET /api/v1/deals/{id}         → 401    POST /api/v1/deals              → 401
GET /api/v1/deals/{id}/history → 401    PATCH /api/v1/settings          → 401
GET /api/v1/forecast           → 401
GET /api/v1/settings           → 401    GET /health → 200 / GET / → 200 (公開のまま)
GET /api/v1/stages             → 401
```

nene-records の Critical に相当する「未認証で読める管理 GET」は **1 件も無い**。

### F-02 同型（JWT 越境 replay）— 存在しない ✅

**アーキテクチャ根拠:** nene-records は org チェックを capability マップされたルートでのみ実施し、
未マップのルートで抜けた。nene-deal は org を **署名済み JWT クレーム（`org`）→ request-scoped
holder → 全リポジトリクエリの `WHERE organization_id = ?`** で強制する。ルート単位のマッピングに
依存せず**データ層で普遍的に**効く。ユーザ管理系（`findById` が org 非スコープ）も
`GetUserHandler` / `UpdateUserUseCase` / `DeleteUserUseCase` が
`$user->organizationId !== $organization->id()` を明示チェックし fail-closed。

**live 実証（org-B admin のトークンを org-A リソースへ replay → すべて 404）:**

```
B→A GET   /api/v1/deals/{A}            → 404    B→A GET    /api/v1/users/{A}   → 404
B→A GET   /api/v1/deals/{A}/history    → 404    B→A PATCH  /api/v1/users/{A}   → 404
B→A PATCH /api/v1/deals/{A}            → 404    B→A DELETE /api/v1/users/{A}   → 404
B→A DELETE/api/v1/deals/{A}            → 404    B→A PATCH  /api/v1/stages/{A}  → 404
B→A POST  /api/v1/deals/{A}/stage-change→ 404
B→A POST  /api/v1/deals/{A}/restore    → 404    一覧: B の deals/users に org-A 行は混入しない
B→A POST  /api/v1/deals/{A}/invoice-handoff → 404
```

- **陽性対照:** org-B トークンが**自 org**のリソースへ到達するのは正常（`GET deal`/`GET user` → 200）。
- `X-Organization-Slug: org-a` を添えても署名クレームを**上書き不可**（→ 404）。

nene-records の Medium に相当する「JWT replay での越境読み取り」は **成立しない**。

### 付随スポットチェック

`alg:none` 偽造・不正形式 bearer → 401。`X-Powered-By` は全応答で除去済み（#122 F-3）。

## 未実施 / スコープ外（正直な明記）

- **フロントエンド（React SPA）診断は未実施**（本ラウンドも API/バックエンド限定）。
- 本ラウンドは F-01/F-02 同型の網羅実証に集中。インジェクション/数値境界/CSV/ヘッダ等の
  網羅は先行の [2026-07-13](2026-07-13-assessment.md) を参照（そこで確定した 3 件は #122 で修正済み）。
- `composer audit`（依存 CVE）未実施。login throttle の実弾ブルートフォースは DoS 回避で未実施。
- 検証は MySQL 8.4。他アダプタ差分は未網羅。

## 結論

nene-records で Critical/Medium だった2クラス（未認証 admin GET・JWT 越境 replay）は、
nene-deal では **blocklist 認可＋データ層 org バインディング**という設計により**構造的に存在せず**、
全ルート行列の live 実証（PASS=34 / EXPOSED=0）で確認した。**新規修正は不要。**
先行 #122 と合わせ、nene-deal の診断記録（EXPOSED 0・再現ハーネス有）が揃った。
