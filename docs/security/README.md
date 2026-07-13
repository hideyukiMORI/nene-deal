# セキュリティ診断履歴

NeNe Deal に対して実施した**認可された自己/maintainer-run 診断**（保守者が自リポ・隔離
環境に対して行う診断。第三者ペネトレーションではない）の記録です。

## 診断レポート

| 日付 | レポート | 概要 |
| --- | --- | --- |
| 2026-07-13 | [Assessment](2026-07-13-assessment.md) | 全面診断（テナント分離・JWT/認証・状態遷移・数値境界・CSV・ヘッダ/CORS・情報漏洩）。実アプリ+MySQL で実弾。Finding 3 件（入力最大長/日付 500・owner 越境 email 漏洩・X-Powered-By）を修正し EXPOSED 0 を確認 |
| 2026-07-14 | [Red-team round](2026-07-14-assessment-redteam.md) | nene-records の実在 finding（F-01 未認証 admin GET Critical / F-02 JWT 越境 replay Medium）と同型が無いか全ルート行列で live 実証。blocklist 認可＋データ層 org バインディングにより構造的に不在（PASS=34 / EXPOSED=0・新規修正なし） |

各レポートは Finding（深刻度 / 証拠 / 推奨）、検証済みの堅牢性、Remediation、未実施の明記を含みます。

## 再現ハーネス

[`harness/`](harness/) に、診断に使ったローカル再現環境（Docker）と道具を収めています。
バックエンドをスタブせず**実アプリ（PHP 8.4 + MySQL 8.4、strict mode = 本番同型）を起動して**
叩くための最小構成です。

> ⚠️ 認可された自己所有アプリ・隔離環境での検証専用。本番へは実施しない。第三者システムへの
> 無断使用は禁止。シークレット類は使い捨て。

### 実行方法

```bash
cd docs/security/harness
cp .env.app.example .env.app
php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"   # → .env.app の NENE2_LOCAL_JWT_SECRET に設定
./run.sh                                            # up + migrate + seed（app:8119 / db:3319）
curl -s localhost:8119/health

# 実弾バッテリ（PASS/EXPOSED を判定表示）
# ログインしてトークンを tokens.env に用意してから実行（README 参照）
./attack.sh

# 解体（ボリューム破棄）
docker compose -p nene-deal-sectest down -v
```

- `mint.php` … HS256 JWT 生成ツール（alg/exp/署名/org クレーム検証用）。
- `attack.sh` … テナント分離・JWT・RBAC・状態遷移・数値境界・owner 漏洩・CSV・ヘッダ/CORS・
  情報漏洩を網羅する回帰バッテリ。
- 秘密情報（`.env.app` 実体・`tokens.env`・`*.log`）は `.gitignore` 済みでコミットしません。
- ポート 8119 / 3319 は NeNe Deal 固定レンジ（81\*\*）で、dev スタック（8110 / 3310）と非衝突。
