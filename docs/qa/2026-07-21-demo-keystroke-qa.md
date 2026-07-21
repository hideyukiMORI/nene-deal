# デモ打鍵QA 仕様書 — NeNe Deal（2026-07-21）

- **制定**: 施主 hide（07-21）「正常系・異常系の全ルートを設定→テスト仕様書に残し→実行結果も記録」
- **体制**: 仕様起草＋実行 = Deal リナ／観点枠＋敵対的レビュー = 統合リナ（hub, Fable）。二段構え（起草→hub 査読→実行）を必須とする。
- **テンプレ**: `_work/reports/2026-07-21-demo-qa-template.md`（hub 管理・観点カタログ A〜F の正本）。実装1例目 = vault `docs/qa/2026-07-21-demo-keystroke-qa.md`（#276・55シナリオ）。
- **将来接続**: 本仕様書は T2（Playwright スクリプト化）の種。シナリオ ID（`DEA-<観点>-<連番>`）は e2e 移植時にそのまま spec 名になる前提で採番する。
- **Issue**: #167（起草のみ・実行は hub 承認後）

> **本ドキュメントは起草段階。** §2 の各シナリオ「結果／証拠」欄と §3 実行記録は、hub のレビュー PASS 後にブラウザ打鍵で埋める。現時点では手順と期待結果のみを確定させる。

---

## 0. スコープと安全規範

- **対象は公開デモ環境のみ**（`/demo/standard` の disposable org・#69 Nene2\Demo）。本番顧客データには触れない。書き込み系シナリオは nightly reset / TTL 3h で消える demo org 内で完結させる。
- 破壊的操作（deal 削除・stage 削除）は「デモとして見せてよい範囲」でのみ実行。Deal の「削除」は **soft-delete（→recycle bin→restore 可）**（#47）＝hard-delete でない。この前提を各シナリオに明記する。
- **実行中はデモへのデプロイ・データ操作を凍結**（hub 管理）。本仕様書の起草は repo 内ドキュメント作業のみで凍結対象外。デプロイ凍結の要否は実行着手時に hub へ申告（deal に本番反映予定が無ければ不要）。
- 実行記録に **ビルド SHA・実行日時（TZ 明記）・ブラウザ/バージョン・画面幅・デモ URL** を必ず残す（§3）。
- demo org の mint（disposable org 発行）には **レート制限（30/h・200-org ceiling）**があるため、`/demo/standard` の連打シナリオ（A-6）は節度を持って実行し、上限挙動を意図的に見るときのみ踏む。

### 0.1 起草時に判明した構成前提（hub 査読の要確認事項）

打鍵前にフロント実装を精査した結果、テンプレ既定と deal 実装が食い違う／重点化すべき点を先に固定する。**hub はここを最初に確認してほしい**（vault §0.1 と同型）。

| # | テンプレ既定 | Deal 実装の実際 | 本仕様書での扱い |
|---|---|---|---|
| P-1 | A-6 `/demo/standard` `/demo/guided` が SPA ルート | `/demo/standard` は **PHP 側デモ入口**（disposable org 起動点・#69）。`/demo/guided` ルートは**無く**、guided 相当は **固定 demo org**（`tools/seed-demo.php` の配布資格情報）で代替 | 公開デモへの打鍵として **A-6 に採用**（「SPA ルートでなく配信入口」と注記・DEA-A6-*） |
| P-2 | E-1/E-3 テーマ・言語切替 | **ランタイム ThemeToggle/LangToggle が存在する**（`shared/ui/components/`・07-21 C3a-2b で presentation 化）。light/dark ＋ ja/en | E-1・E-3 は **該当あり**（vault は該当なしだったが deal は逆・切替直後の全画面崩れを見る） |
| P-3 | — | **意匠付き catch-all 404（NotFoundPage・router `*`）あり** | D-2・F-3 で「未知 URL が意匠付き 404 に落ちるか」を確認（良好側の想定） |
| P-4 | B-7 ファイル入力 / B-8 エクスポート注入 | **ファイル upload/添付は無し** → **B-7 該当なし**。**audit CSV export あり**（#53 で `CsvWriter` により formula-injection を閉じ済み）→ **B-8 該当あり**（中和の実挙動を打鍵で裏取り） | B-7=該当なし（§1）／B-8=audit CSV に対して実施 |
| P-5 | A-1 CRUD の delete | Deal は **soft-delete + restore（recycle bin・#47）**。stage は delete あり | A-1 は「作成→閲覧→編集→soft-delete→restore」の一巡で書く |
| P-6 | D-3 複数タブ競合 | Deal は **kanban D&D（`use-kanban-dnd`）＋ stage move**。楽観更新の競合・タブ間同時編集・楽観ロック（vault で last-write-wins 発見）を **重点** | D-3 を D&D＋タブ競合＋stage move 競合で厚めに（board #84 no-refetch-deleted-deal も関連） |
| P-7 | A-3 主要業務フロー | Deal の「売り」= pipeline（create→stage move→won→**invoice-handoff**）。**hub 追加観点**: invoice-handoff の連携がデモで実際に動くかを実証（LP 連携主張の裏取り）。動かなければ「導線の現状」として記録 | DEA-A3-* で handoff を実挙動まで打鍵 |
| P-8 | B-1 数値 / E-5 書式 | 金額は **`amount_cents` = 円×100 で DB 格納**（board #81・フリート規約 JPY=円そのままと不整合の既知懸案）。入力/表示の桁・境界を重点 | B-1（0/負/上限）・E-5（円書式）で金額を厚めに |
| P-9 | C-3 権限別 | ロールは **admin / operator の2種**（superadmin は #111 stop-gate＝未実装）。admin 専用: users / stages / settings / audit | C-3 は admin↔operator の見え方差で書く |

---

## 1. 観点対応表（漏れ防止の網・hub 査読はここを機械的に突く）

### A. 正常系
| 項目 | 該当シナリオ / 該当なし理由 |
|---|---|
| A-1 全エンティティ CRUD 一巡 | DEA-A1-01（deal: 作成→board→detail→edit→soft-delete→restore）, DEA-A1-02（stage: 作成→rename→reorder→delete・terminal は不可）, DEA-A1-03（user: 作成→role変更→disable→enable→delete・self 不可）, DEA-A1-04（settings: forecast closing day 更新・singleton） |
| A-2 一覧（検索/フィルタ/ソート/ページング/0件/大量） | DEA-A2-01（board フィルタ = show won/lost・show deleted）, DEA-A2-02（board 0件 EmptyState）, DEA-A2-03（audit from/to フィルタ＋invalidRange）。**検索・ソート・ページングの UI は board/一覧に無し**（kanban は全件列・audit は日付範囲のみ）を明記 |
| A-3 主要業務フロー（売りの導線） | DEA-A3-01（pipeline: 作成→DnD stage move→won→**invoice-handoff 実挙動**＝P-7 裏取り）, DEA-A3-02（audit CSV export 一巡＝証跡の売り） |
| A-4 ダッシュボード・集計値の整合 | DEA-A4-01（forecast strip の weighted/open/won ↔ board 各 column の client 集計の整合・**server 集計と client 集計の二重計算**を突く） |
| A-5 ナビ全リンク一巡 | DEA-A5-01（AppShell nav 全項目〔board/stages/users/audit/settings〕＋detail 往復＋mobile tabs） |
| A-6 デモ固有導線 | DEA-A6-01（/demo/standard: disposable org・admin seat・board 着地）, DEA-A6-02（/demo/standard 再訪・連打＝毎回新 org・mint レート制限 30/h の節度）, DEA-A6-03（リロードで session 消滅＝memory-only token・再訪要）。P-1 注記（SPA ルートでなく配信入口・/demo/guided は無し） |

### B. 異常系 — 入力
| 項目 | 該当シナリオ / 該当なし理由 |
|---|---|
| B-1 境界値 | DEA-B1-01（amount: 0/負/巨大〔円×100 の桁〕/小数）, DEA-B1-02（probability: 0/100/-1/101）, DEA-B1-03（stage sortOrder: 0/負・inline edit の silent guard） |
| B-2 空・必須欠落・空白のみ | DEA-B2-01（create-deal account 空/空白）, DEA-B2-02（user create email 空・password<8）, DEA-B2-03（stage label 空/空白） |
| B-3 型不正 | DEA-B3-01（login email 不正形式＝client 検証なし→server 挙動）, DEA-B3-02（user create email 不正形式＝client email 検証あり）, DEA-B3-03（date 欄に不正形式・native date widget 迂回） |
| B-4 多バイト・絵文字・RTL・HTML/スクリプト | DEA-B4-01（account/note に `<script>alert(1)</script>` → board/detail/timeline/audit の表示エスケープ）, DEA-B4-02（絵文字・RTL・多バイト account/note） |
| B-5 過長入力 | DEA-B5-01（account/note の上限超・巨大貼付） |
| B-6 二重送信・連打 | DEA-B6-01（create/save/handoff/move の連打→pending disable・多重送信防止） |
| B-7 ファイル入出力 | **該当なし**: deal にファイル upload/添付機能が無い（フロント全域に file input・upload エンドポイント無し）。export は CSV のみ（B-8 で扱う） |
| B-8 エクスポート注入 | DEA-B8-01（audit の deal 名/note/field に `=`,`+`,`-`,`@`,`TAB` 始まりの値を仕込み→audit CSV export のセルで式化しないか＝#53 `CsvWriter` 中和の実挙動裏取り） |

### C. 異常系 — 認証・権限・境界
| 項目 | 該当シナリオ / 該当なし理由 |
|---|---|
| C-1 未ログイン保護 URL 直叩き | DEA-C1-01（token 無しで `/`・`/deals/:id`・`/users` 直叩き＝`VITE_REQUIRE_LOGIN` 挙動〔fail-closed か素通りか〕を実測） |
| C-2 存在しない/他 org の ID 直叩き | DEA-C2-01（`/deals/<bogus>`→detail notFound）, DEA-C2-02（他 demo org の deal id 直叩き＝404/403・情報漏えい無） |
| C-3 権限別表示 | DEA-C3-01（operator で `/users`等直叩き→`/` redirect・nav 非表示）, DEA-C3-02（**RequireAdmin の loading フラッシュ**＝admin content 一瞬表示→redirect の有無） |
| C-4 セッション切れ後の操作 | DEA-C4-01（token 失効/クリア後に操作→401 マッピング・データ喪失） |
| C-5 ログアウト→戻るでの閲覧可否 | DEA-C5-01（signout→ブラウザ戻る＝キャッシュ露出・token memory-only の効き） |

### D. 異常系 — 遷移・状態
| 項目 | 該当シナリオ / 該当なし理由 |
|---|---|
| D-1 入力途中のリロード/戻る/進む | DEA-D1-01（create-deal/edit 入力中→reload/戻る＝データ喪失の警告有無） |
| D-2 深いリンク直行 | DEA-D2-01（`/deals/:id` をブックマーク/直行・未知 URL→意匠付き 404） |
| D-3 複数タブ同時操作 | DEA-D3-01（2タブで同一 deal を edit＝last-write-wins/楽観ロック＝vault 鏡）, DEA-D3-02（2タブで同一 deal を別 stage へ DnD＝optimistic 競合・server 整合）, DEA-D3-03（別タブで削除済みの deal を操作＝board #84 no-refetch-deleted 関連）, DEA-D3-04（**高速連続 stage move の race**＝楽観ロックの連打破れ・hub 査読補強） |
| D-4 遅い回線/読み込み中の連打 | DEA-D4-01（throttle 下で DnD/保存 連打＝optimistic 多重・loading 状態の壊れ） |

### E. 表示・国際化・テーマ
| 項目 | 該当シナリオ / 該当なし理由 |
|---|---|
| E-1 テーマ切替 | DEA-E1-01（ThemeToggle light↔dark＝board/detail/admin/login 全画面の切替直後の崩れ） |
| E-2 レスポンシブ | DEA-E2-01（375px/768px＝kanban 横スクロール・mobile 上下 bar・account sheet・横スクロール溢れ） |
| E-3 言語切替 | DEA-E3-01（LangToggle ja↔en＝未訳キー生露出・レイアウト崩れ）, DEA-E3-02（言語スイッチャの endonym `日本語`/`EN` が非翻訳固定＝判例19 の意図確認） |
| E-4 日時・タイムゾーン表示 | DEA-E4-01（**重点**: activity timeline は local TZ〔Intl medium/short〕・audit の from/to 既定は UTC 境界〔`toISOString`〕＝JST 深夜/早朝で off-by-one が出ないか・作成日時/期限/監査ログの一貫性） |
| E-5 通貨・数値・単位の書式 | DEA-E5-01（`formatMoneyJpy`＝¥桁区切り・小数無し・0/負/巨大の表示） |
| E-6 長い名前/長いタイトルの折返し・省略 | DEA-E6-01（長い account 名・stage label＝card/detail/nav/timeline の折返し/省略） |

### F. デモ品質（営業視点）
| 項目 | 該当シナリオ / 該当なし理由 |
|---|---|
| F-1 初見の到達性 | DEA-F1-01（demo 着地→pipeline の「作成→move→won→handoff」に迷わず到達できるか・ガイド有無） |
| F-2 demo データの品質 | DEA-F2-01（seed pipeline の自然さ・テスト残骸・**login hero の固定値 `12`/`￥3.15M`/`99.9%`** が実データでない旨の許容判定） |
| F-3 エラーメッセージの文言品質 | DEA-F3-01（RootErrorBoundary の bilingual fallback・stacktrace 非露出・開発者向け文言/生 problem-details 非露出） |
| F-4 コンソール/ネットワークエラー | DEA-F4-01（DevTools 開放で全シナリオ通し＝console error・4xx/5xx の常時発生無し） |

---

## 2. シナリオ（DEA-<観点>-<連番>）

> 書式: 分類 / 前提 / 手順 / 期待（具体文言）/ 結果（実行時）/ 証拠（実行時）/ 発見（hub 仕分け）。金額は「入力=円・格納=cents（円×100）」に注意。

### DEA-A1-01: deal の CRUD 一巡（作成→閲覧→編集→soft-delete→restore）
- 分類: A-1 / 正常
- 前提: demo org に admin で着地（`/demo/standard`）・board 表示
- 手順: 1. 「Add deal」→ account="Acme K.K." / amount=1200000 / probability=60 / stage=先頭 / close date=空 で Create 2. 該当 stage 列にカード出現を確認→カードの詳細リンクで `/deals/:id` へ 3. Edit で amount=1500000・note 追記→Save 4. Danger zone「Delete this deal」→confirm 5. board で「Show deleted」ON→当該カードの Restore
- 期待: 1. カードに `¥1,200,000`・prob bar 60%・account 表示 2. detail が同値・作成が activity timeline に `created` として出る 3. `Saved`/`Changes stored` トースト・detail が `¥1,500,000`・timeline に amount 変更（`¥1,200,000 → ¥1,500,000`）と note 変更 4. confirm 文言「Delete this deal? You can restore it later.」→削除トースト→`/` へ遷移・通常表示では消える 5. Restore トースト→通常列へ復帰・timeline に `deleted`→`restored`
- 結果:
- 証拠:
- 発見:

### DEA-A1-02: stage の作成→rename→reorder→delete（terminal 不可）
- 分類: A-1 / 正常
- 前提: admin・`/stages`
- 手順: 1. label="Qualifying" / sortOrder=15 で Create 2. 作成行を Edit→label/ sortOrder 変更→Save 3. sortOrder を変え並び替え 4. 当該 stage を Delete（confirm）5. Won/Terminal stage の行に edit/delete が無いことを確認
- 期待: 1. 一覧に slug 付きで追加・sortOrder 順に配置 2. 変更反映（rename は inline・zod 無し＝空 label は元値フォールバック・負/NaN は無反応）3. 並び順反映 4. confirm「Delete this stage?」→削除 5. terminal 行に操作ボタン無し
- 結果:
- 証拠:
- 発見:

### DEA-A1-03: user の作成→role 変更→disable→enable→delete（self 不可）
- 分類: A-1 / 正常
- 前提: admin・`/users`
- 手順: 1. email="ops2@example.com" / password="password8" / role=operator で Create 2. 行の role Select を admin に変更 3. Disable（confirm）→Enable 4. Delete（confirm）5. 自分の行で role Select が disabled・disable/delete が非表示なことを確認
- 期待: 1. 一覧に追加（avatar=先頭字）2. role 反映 3. `Disabled` バッジ↔解除 4. confirm「Delete this user?」→削除 5. self は role 変更/disable/delete 不可
- 結果:
- 証拠:
- 発見:

### DEA-A1-04: settings（forecast closing day）更新
- 分類: A-1 / 正常
- 前提: admin・`/settings`
- 手順: 1. closing day を「20」に変更→Save 2. 「月末（month-end）」に変更→Save
- 期待: 1. `Saved` トースト・forecast 期間が締め日基準で再計算（board の月ラベル/集計に反映）2. month-end は null 永続・hint 文言（21日〜20日集計）が整合
- 結果:
- 証拠:
- 発見:

### DEA-A2-01: board フィルタ（show won/lost・show deleted）
- 分類: A-2 / 正常
- 前提: won/lost と削除済みを含む pipeline
- 手順: 1. 「Show won/lost」トグル ON/OFF 2. 「Show deleted」トグル ON/OFF
- 期待: 1. terminal 列/カードの表示・非表示が切替（`includeTerminal` で再取得）2. 削除済みカードが Deleted バッジ＋Restore 付きで表示/非表示
- 結果:
- 証拠:
- 発見:

### DEA-A2-02: board 0件（EmptyState）
- 分類: A-2 / 正常
- 前提: deal を全て削除 or 空 org
- 手順: 1. 全 column が空の状態で board を表示
- 期待: `EmptyState`（`board.empty.title`/`description`）が出る・列崩れ無し
- 結果:
- 証拠:
- 発見:

### DEA-A2-03: audit フィルタ（from/to・invalidRange）
- 分類: A-2 / 正常＋異常境界
- 前提: admin・`/audit`
- 手順: 1. from=今月1日・to=今日で表示 2. from を to より後に設定
- 期待: 1. 既定で当月範囲・列 `timestamp/actor/action/deal_id/field/before/after` 2. from>to で「The start date must be on or before the end date.」・Download 無効化
- 結果:
- 証拠:
- 発見:

### DEA-A3-01: pipeline 主要導線＋invoice-handoff 実挙動（P-7 裏取り）
- 分類: A-3 / 正常（売りの導線・hub 追加観点）
- 前提: admin・board
- 手順: 1. deal 作成 2. DnD で当該カードを「won」stage へ移動（cross-stage）3. detail を開く 4. Handoff カードの「Send to Invoice」を押す 5. （比較）won でない deal でも「Send to Invoice」ボタンが出るか・押下時の挙動
- 期待: 1-2. move トースト（`account → stage`）・optimistic 反映 3. detail に Won バッジ 4. **連携が実装/デモで動くなら** linked 状態（`clientId`/`quoteId` 表示）へ 5. フロントはボタンを won で gate しない＝non-won で押すと server 側 won 制約のエラーが `handoff` 欄に出る。**連携が動かない/未接続なら「導線の現状」として記録**（LP 連携主張の裏取り）
- 結果:
- 証拠:
- 発見:

### DEA-A3-02: audit CSV export 一巡
- 分類: A-3 / 正常
- 前提: admin・`/audit`・操作履歴あり
- 手順: 1. 範囲指定→Download 2. DL した CSV を開く
- 期待: `audit-{from}_{to}.csv` が DL・success トースト・列が UI 表示と一致・操作が行として入る
- 結果:
- 証拠:
- 発見:

### DEA-A4-01: forecast 集計の整合（server strip ↔ client column の二重計算）
- 分類: A-4 / 正常（整合性）
- 前提: 複数 stage に prob 付き deal
- 手順: 1. board stat strip の Pipeline total・Weighted・Open count・Won this month を控える 2. 各 column の summary（count・weighted）を合算して比較
- 期待: strip（server 集計）と column 合算（client `floor(amount*prob/100)`）が論理的に整合（丸め差以外の乖離が無い）。**乖離があれば発見**（二重計算の不一致）
- 結果:
- 証拠:
- 発見:

### DEA-A5-01: ナビ全リンク一巡
- 分類: A-5 / 正常
- 前提: admin
- 手順: 1. top-nav の board/stages/users/audit/settings を順に 2. deal detail へ入って戻る 3. mobile 幅で bottom tabs＋account sheet
- 期待: 全リンク遷移可・active 表示・戻り導線正常・404 に落ちない
- 結果:
- 証拠:
- 発見:

### DEA-A6-01: /demo/standard 入口（disposable org・admin seat）
- 分類: A-6 / 正常（デモ導線）
- 前提: 未ログイン・公開デモ URL
- 手順: 1. `/demo/standard` を開く
- 期待: seat ページ経由で throwaway org の admin として `/`（board）へ着地・seed pipeline 表示・admin nav 全表示
- 結果:
- 証拠:
- 発見:

### DEA-A6-02: /demo/standard 再訪・連打（毎回新 org・レート制限）
- 分類: A-6 / 異常寄り（乱発耐性）
- 前提: 上記
- 手順: 1. `/demo/standard` を数回連続で開く（節度＝上限挙動を見る時のみ多発）
- 期待: 毎回新しい disposable org・前の org のデータは混ざらない・mint レート制限（30/h・200 org 上限）に達したら意匠付きエラー
- 結果:
- 証拠:
- 発見:

### DEA-A6-03: リロードで session 消滅（memory-only token）
- 分類: A-6 / 正常（仕様確認）
- 前提: demo で着地済み
- 手順: 1. board でブラウザリロード
- 期待: token は memory-only＝リロードで未ログイン化（`VITE_REQUIRE_LOGIN` 次第で login へ or 素通り）。再度 demo URL で新 org。データ喪失は仕様
- 結果:
- 証拠:
- 発見:

### DEA-B1-01: amount 境界（0/負/巨大/小数）
- 分類: B-1 / 異常
- 前提: create-deal フォーム
- 手順: 1. amount=0 2. amount=-1 3. amount=99999999999（巨大・円×100 の桁）4. amount=100.5
- 期待: 1. 0 は許容（≥0）2. 負は「Amount must be a whole number of yen, 0 or greater.」3. 巨大値の格納/表示破綻の有無（cents 化での桁溢れ）4. 非整数は int 検証で弾く
- 結果:
- 証拠:
- 発見:

### DEA-B1-02: probability 境界（0/100/-1/101）
- 分類: B-1 / 異常
- 前提: create-deal
- 手順: 1. 0 2. 100 3. -1 4. 101
- 期待: 0/100 許容・範囲外は「Probability must be between 0 and 100.」
- 結果:
- 証拠:
- 発見:

### DEA-B1-03: stage sortOrder 境界（inline edit の silent guard）
- 分類: B-1 / 異常
- 前提: `/stages` の inline edit
- 手順: 1. sortOrder=-1 で Save 2. sortOrder=空/文字 で Save
- 期待: 負/NaN は silent に無反応（保存されない・エラー文言も出ない）＝**文言不在を発見候補**（create 側は「Sort order must be 0 or greater.」あり）
- 結果:
- 証拠:
- 発見:

### DEA-B2-01: create-deal 必須欠落（account 空/空白）
- 分類: B-2 / 異常
- 手順: 1. account 空で Create 2. account=空白のみで Create
- 期待: 「Please enter an account name.」（trim 後 min1）・送信されない
- 結果:
- 証拠:
- 発見:

### DEA-B2-02: user create 必須（email 空・password<8）
- 分類: B-2 / 異常
- 手順: 1. email 空 2. password=7文字
- 期待: 「Please enter an email address.」/「Password must be at least 8 characters.」
- 結果:
- 証拠:
- 発見:

### DEA-B2-03: stage label 空/空白
- 分類: B-2 / 異常
- 手順: 1. create で label 空/空白→Create
- 期待: 「（stages.validation.labelRequired）」・送信されない
- 結果:
- 証拠:
- 発見:

### DEA-B3-01: login email 型不正（client 検証なし）
- 分類: B-3 / 異常
- 手順: 1. email="notanemail"・password 適当で Sign in
- 期待: login の zod は min1 のみ＝形式検証せず送信→server 401 を単一文言「Invalid email or password.」に集約（情報漏えい無し）
- 結果:
- 証拠:
- 発見:

### DEA-B3-02: user create email 型不正（client 検証あり）
- 分類: B-3 / 異常
- 手順: 1. email="notanemail" で Create
- 期待: client の `z.email` で「Please enter an email address.」（login と挙動差＝仕様として記録）
- 結果:
- 証拠:
- 発見:

### DEA-B3-03: date 欄に不正形式
- 分類: B-3 / 異常
- 手順: 1. expected close date / audit from に native date widget 外の不正入力を試みる
- 期待: native date input が形式を制約・不正は空扱い（喪失/破綻無し）
- 結果:
- 証拠:
- 発見:

### DEA-B4-01: XSS 文字列の表示エスケープ
- 分類: B-4 / 異常（セキュリティ表示）
- 手順: 1. account/note に `<script>alert(1)</script>` と `"><img src=x onerror=alert(1)>` を保存 2. board card・detail・activity timeline・audit CSV/画面 の各 venue で表示
- 期待: 全 venue で**文字列としてエスケープ表示**・スクリプト実行/DOM 注入無し（React 既定エスケープ＋audit diff 表示）
- 結果:
- 証拠:
- 発見:

### DEA-B4-02: 多バイト・絵文字・RTL
- 分類: B-4 / 異常
- 手順: 1. account/note に 絵文字・アラビア語（RTL）・全角多バイトを保存→各画面表示
- 期待: 文字化け無し・RTL 混在でレイアウト破綻無し・timeline/audit でも保持
- 結果:
- 証拠:
- 発見:

### DEA-B5-01: 過長入力
- 分類: B-5 / 異常
- 手順: 1. account/note に 10,000 字級を貼付→Save
- 期待: 上限（server 側）で弾くか受理か・受理時に card/detail/timeline の折返しが破綻しない・422 は validation 文言へマップ
- 結果:
- 証拠:
- 発見:

### DEA-B6-01: 二重送信・連打
- 分類: B-6 / 異常
- 手順: 1. Create/Save/Send to Invoice/stage Select/DnD を素早く二連打
- 期待: pending 中はボタン disabled（`disabled={*Pending}`）・多重 POST が飛ばない・optimistic が二重適用されない
- 結果:
- 証拠:
- 発見:

### DEA-B8-01: audit CSV インジェクション中和（#53 裏取り）
- 分類: B-8 / 異常（営業リスク）
- 前提: admin
- 手順: 1. account/note に `=1+1`・`=HYPERLINK("http://evil","x")`・`+1`・`-1`・`@x`・TAB 始まりの値を保存（→audit に記録される）2. `/audit` で export→CSV を Excel/表計算で開く
- 期待: CSV セルで**式として評価されない**（先頭中和＝`CsvWriter` の formula-injection 対策・#53）。評価されたら🔴（営業直結）
- 結果:
- 証拠:
- 発見:

### DEA-C1-01: 未ログイン保護 URL 直叩き
- 分類: C-1 / 異常（認証境界）
- 手順: 1. token 無しで `/`・`/deals/:id`・`/users` を直叩き
- 期待: `VITE_REQUIRE_LOGIN=true` なら `/login` へ fail-closed。公開デモ設定での実挙動を実測し明記（素通り時は `useCurrentUser` 未解決で admin nav 非表示＝データは出ない）。**認証境界の実挙動を発見として固定**
- 結果:
- 証拠:
- 発見:

### DEA-C2-01: 存在しない deal id 直叩き
- 分類: C-2 / 異常
- 手順: 1. `/deals/nonexistent-id`
- 期待: detail が `notFound`（EmptyState）・情報漏えい無し・500 でない
- 結果:
- 証拠:
- 発見:

### DEA-C2-02: 他 org の id 直叩き（テナント分離）
- 分類: C-2 / 異常（重要）
- 前提: demo org A で得た token・別 org B の deal id を推測
- 手順: 1. `/deals/<other-org-id>`
- 期待: 404/403 で他 org データを返さない（テナント分離・情報漏えい無し）
- 結果:
- 証拠:
- 発見:

### DEA-C3-01: operator で admin URL 直叩き
- 分類: C-3 / 異常（権限）
- 前提: operator ロールのユーザ（admin が作成）
- 手順: 1. operator で login 2. nav に stages/users/audit/settings が無いこと 3. `/users` を直叩き
- 期待: nav 非表示・直叩きは `RequireAdmin` で `/` へ redirect
- 結果:
- 証拠:
- 発見:

### DEA-C3-02: RequireAdmin の loading フラッシュ
- 分類: C-3 / 異常（境界・発見候補）
- 前提: operator・低速回線
- 手順: 1. operator で `/users` を直叩き（current-user query が解決する前を観測）
- 期待: current-user 未解決の間は children を通す実装＝**admin content が一瞬描画されてから redirect する可能性**。露出の有無を発見として固定
- 結果:
- 証拠:
- 発見:

### DEA-C4-01: セッション切れ後の操作
- 分類: C-4 / 異常
- 手順: 1. 着地後に token を無効化（別セッションで失効 or 手動クリア）→保護操作（保存/移動）
- 期待: 401→`common.error.unauthorized` マップ or login 誘導・データ喪失の警告/挙動が破綻しない
- 結果:
- 証拠:
- 発見:

### DEA-C5-01: ログアウト→ブラウザ戻る
- 分類: C-5 / 異常
- 手順: 1. SignOut（token クリア→`/login`）2. ブラウザ「戻る」で board へ
- 期待: token memory-only ＝戻っても保護データは再取得できない（キャッシュから機微情報が露出しない）
- 結果:
- 証拠:
- 発見:

### DEA-D1-01: 入力途中のリロード/戻る
- 分類: D-1 / 異常
- 手順: 1. create-deal/edit に入力途中でリロード・ブラウザ戻る
- 期待: 未保存データ喪失（警告の有無を記録）・破綻無し。RHF 状態は永続しない仕様
- 結果:
- 証拠:
- 発見:

### DEA-D2-01: 深いリンク直行／未知 URL
- 分類: D-2 / 異常
- 手順: 1. `/deals/:id` を直行 2. `/zzz-unknown` を直行
- 期待: 1. detail 直接描画（認証前提）2. 意匠付き `NotFoundPage`（catch-all `*`・P-3）
- 結果:
- 証拠:
- 発見:

### DEA-D3-01: 複数タブ 同一 deal 編集競合（last-write-wins）
- 分類: D-3 / 異常（重点・vault 鏡）
- 前提: 2タブで同一 deal detail
- 手順: 1. タブA で amount 変更・Save 2. タブB（古い値）で note 変更・Save
- 期待: 楽観ロックが無ければ **last-write-wins**（タブB の保存がタブA の変更を上書き）。挙動を発見として固定（vault の last-write-wins 発見と同型か）
- 結果:
- 証拠:
- 発見:

### DEA-D3-02: 複数タブ DnD stage move 競合
- 分類: D-3 / 異常（重点）
- 手順: 1. 2タブで同一カードを別 stage へ DnD 2. 双方の optimistic 適用と server 反映を観測
- 期待: optimistic は各タブで即時反映・server は後勝ち・不整合時に stale が残らないか（refetch/invalidate の効き）
- 結果:
- 証拠:
- 発見:

### DEA-D3-03: 別タブで削除済み deal を操作
- 分類: D-3 / 異常（board #84 関連）
- 手順: 1. タブA で deal を削除 2. タブB（未更新）で同 deal を move/edit
- 期待: 削除済みへの操作が 404/409 に落ち、UI が壊れない・削除済みを誤って再取得しない（#84 no-refetch-deleted-deal）
- 結果:
- 証拠:
- 発見:

### DEA-D3-04: 高速連続 stage move の race（楽観ロックの連打破れ・hub 査読補強）
- 分類: D-3 / 異常（重点・vault mint 連打の教訓）
- 前提: 1枚のカードと複数 stage
- 手順: 1. 同一カードを A→B→C→… と**高速で連続 DnD**（前の move の POST が返る前に次を撃つ）2. 単一タブ内での連続 move ＋（可能なら）2タブから交互に高速 move
- 期待: 各 move の optimistic 適用が破綻せず、最終 server 状態と UI が一致（stale 残留・二重適用・カード消失・列不整合が起きない）。楽観ロック（last-write-wins）が連続 move で最も破れやすいため、**race による不整合が出れば🔴で即再現確定**（単発 DEA-D3-02 との差分を記録）
- 結果:
- 証拠:
- 発見:

### DEA-D4-01: 遅い回線での DnD/保存 連打
- 分類: D-4 / 異常
- 前提: DevTools で slow 3G
- 手順: 1. DnD/保存を読み込み中に連打
- 期待: loading 状態が壊れない・多重リクエストが飛ばない・optimistic のロールバックが正しく効く
- 結果:
- 証拠:
- 発見:

### DEA-E1-01: テーマ切替 light/dark
- 分類: E-1 / 表示
- 手順: 1. 各画面（login/board/detail/stages/users/audit/settings）で ThemeToggle を light↔dark
- 期待: 切替直後に全画面で崩れ無し・コントラスト確保・data-theme 反映（※W3 再生成の対象＝意匠指摘は W3 台帳送り）
- 結果:
- 証拠:
- 発見:

### DEA-E2-01: レスポンシブ 375/768
- 分類: E-2 / 表示
- 手順: 1. 375px と 768px で全画面 2. board の横スクロール・mobile top/bottom bar・account sheet
- 期待: 横スクロールは kanban 内に限定・body 溢れ無し・mobile UI が機能・タップ領域確保
- 結果:
- 証拠:
- 発見:

### DEA-E3-01: 言語切替 ja/en
- 分類: E-3 / 表示
- 手順: 1. LangToggle で ja↔en を各画面で
- 期待: 未訳キーの生露出無し（`en.ts` は `MessageCatalog` 型で欠落=コンパイルエラーのはず）・レイアウト崩れ無し・localStorage 永続
- 結果:
- 証拠:
- 発見:

### DEA-E3-02: 言語スイッチャの endonym
- 分類: E-3 / 表示（仕様確認）
- 手順: 1. LangToggle のラベルを確認
- 期待: `日本語`/`EN` が言語問わず自称固定表示（意図的な非翻訳・判例19）
- 結果:
- 証拠:
- 発見:

### DEA-E4-01: 日時・タイムゾーン表示（重点）
- 分類: E-4 / 表示（重点・発見直結）
- 手順: 1. deal を操作し activity timeline の日時を確認 2. `/audit` の既定 from/to と timestamp 列 3. JST 深夜〜早朝の時間帯で audit 既定範囲を確認
- 期待: timeline は local TZ（`Intl` medium/short）。audit の `firstOfMonth()`/`today()` は `toISOString()`＝**UTC 日付境界**＝JST では日付が1日ズレうる（当月1日/当日が UTC 基準）。**表示 TZ の不一致（timeline=local / audit 既定=UTC）を発見として固定**（vault #228 の TZ 系と同型の記録対象）
- 結果:
- 証拠:
- 発見:

### DEA-E5-01: 金額・数値の書式
- 分類: E-5 / 表示
- 手順: 1. 0・1,234,567・巨大・（負は入力不可だが）表示箇所を確認
- 期待: `¥1,234,567` 形式（桁区切り・小数無し・JPY 記号）・card/detail/forecast/timeline で一貫
- 結果:
- 証拠:
- 発見:

### DEA-E6-01: 長い名前の折返し/省略
- 分類: E-6 / 表示
- 手順: 1. account/stage label に 100 字級を入れ card/detail/nav/timeline を確認
- 期待: 折返し or 省略（`ellipsis`）でレイアウト破綻無し・はみ出し無し
- 結果:
- 証拠:
- 発見:

### DEA-F1-01: 初見の到達性
- 分類: F-1 / デモ品質
- 手順: 1. `/demo/standard` 着地から「作成→move→won→handoff」まで初見目線で辿る
- 期待: 主要導線に迷わず到達・空状態でも次アクションが分かる（ガイド/EmptyState 文言）
- 結果:
- 証拠:
- 発見:

### DEA-F2-01: demo データ品質
- 分類: F-2 / デモ品質
- 手順: 1. seed pipeline のデータ 2. login hero の `12`/`￥3.15M`/`99.9%`
- 期待: 不自然な Lorem/テスト残骸が無い・login hero の固定値が「実データでない飾り」として営業上許容か判定（動的でないことを記録）
- 結果:
- 証拠:
- 発見:

### DEA-F3-01: エラー文言の品質
- 分類: F-3 / デモ品質
- 手順: 1. 意図的に 500/例外を誘発（可能なら）2. 各 API エラーのトースト文言
- 期待: RootErrorBoundary は bilingual の穏当な文言（stacktrace 非露出）・トーストは利用者向け文言（生 problem-details/開発者文言の露出無し）
- 結果:
- 証拠:
- 発見:

### DEA-F4-01: コンソール/ネットワークエラー
- 分類: F-4 / デモ品質
- 手順: 1. DevTools（Console+Network）を開いたまま全シナリオを通す
- 期待: 常時発生する console error・想定外の 4xx/5xx が無い（想定内の検証エラーを除く）
- 結果:
- 証拠:
- 発見:

---

## 3. 実行記録

> hub 査読 PASS 後にブラウザ打鍵で記入。**実行日時（TZ 明記）・実行者・ブラウザ/版・画面幅・デモ URL・フロントのビルド SHA** を必ず残す。

- 実行日時 / 実行者 / ブラウザ / 画面幅 / デモ URL / ビルド SHA:
- サマリ: 総数 __ / ✅ __ / 🔴 __ / ⚠️ __ / 未実行 __（理由）
- 🔴・⚠️ は**その場で再現手順を確定**させてから次へ進む（後から再現できない報告を作らない）。

### 実行メモ（🔴/⚠️ の再現手順）
