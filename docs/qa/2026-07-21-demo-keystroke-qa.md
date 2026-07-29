# デモ打鍵QA 仕様書 — NeNe Deal（2026-07-21）

- **制定**: 施主 hide（07-21）「正常系・異常系の全ルートを設定→テスト仕様書に残し→実行結果も記録」
- **体制**: 仕様起草＋実行 = Deal リナ／観点枠＋敵対的レビュー = 統合リナ（hub, Fable）。二段構え（起草→hub 査読→実行）を必須とする。
- **テンプレ**: `_work/reports/2026-07-21-demo-qa-template.md`（hub 管理・観点カタログ A〜F の正本）。実装1例目 = vault `docs/qa/2026-07-21-demo-keystroke-qa.md`（#276・55シナリオ）。
- **将来接続**: 本仕様書は T2（Playwright スクリプト化）の種。シナリオ ID（`DEA-<観点>-<連番>`）は e2e 移植時にそのまま spec 名になる前提で採番する。
- **Issue**: #167（起草のみ・実行は hub 承認後）

> **本ドキュメントは起草段階。** §2 の各シナリオ「結果／証拠」欄と §3 実行記録は、hub のレビュー PASS 後にブラウザ打鍵で埋める。現時点では手順と期待結果のみを確定させる。

---

## 0. スコープと安全規範

- **対象は原則として公開デモ環境**（`/demo/standard` の disposable org・#69 Nene2\Demo）。本番顧客データには触れない。書き込み系シナリオは nightly reset / TTL 3h で消える demo org 内で完結させる。
- **例外（hub 裁定 2026-07-29）— インジェクション系は WAF 無し環境で実施する**。端 WAF（HETEML）が `<script>` / `onerror=` / `">` を含む値を **app 到達前に 403** で落とすため、公開デモで「XSS が発火しない」ことを見ても**アプリ層の防御の証明にならない**（＝偽の合格）。**payload が app に届いていることが判定の前提**である B-4・B-8 等は、**ローカル**（`docker compose` app:8110 ＋ Vite:5187 ＋ `tools/seed-demo.php` seed）で打鍵する。公開デモ側に残すのは **WAF そのものの挙動と 403 時の UX 文言**のみ。実測経緯は §3 batch 2b。
- **打鍵の陽性対照（hub 承認 2026-07-29・batch 2 残余の標準手順）**: 「危険な挙動が観測されない」を ✅ と読む前に、次の3つを満たすこと。満たさなければ ✅ ではなく **CHECK（測れていない）** と記録する。
  1. **検査器の生存** — 意図的に `window.alert()` 等を発火させ、検出側（dialog ハンドラ）が実際に捕まえることを先に確認する。
  2. **payload の到達** — 仕込んだ marker が確かに描画/出力されていること。0 件なら「安全」ではなく「対象が検査範囲に入っていない」。
  3. **対照の陰性** — 中和されるべきでない値（CONTROL）に中和痕が**付かない**こと。全件に無条件付与なら中和の証明にならない。
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
| P-4 | B-7 ファイル入力 / B-8 エクスポート注入 | **ファイル upload/添付は無し** → **B-7 該当なし**。**audit CSV export あり**。formula-injection 中和は**app 側でなく NENE2 vendor の `Nene2\Export\CsvWriter`**（`vendor/hideyukimori/nene2/src/Export/CsvWriter.php:39` `FORMULA_TRIGGERS = ['=','+','-','@',"\t","\r"]`・先頭一致で `'` prefix 中和・default on。コード上に #53 参照は無い＝旧記述の帰属を訂正） → **B-8 該当あり**（中和の実挙動を打鍵で裏取り） | B-7=該当なし（§1）／B-8=audit CSV に対して実施 |
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
| A-1 全エンティティ CRUD 一巡 | DEA-A1-01（deal: 作成→board→detail→edit→soft-delete→restore）, DEA-A1-02（stage: 作成→rename→reorder→delete・terminal は不可・**空前提**）, DEA-A1-03（user: 作成→role変更→disable→enable→delete・self 不可）, DEA-A1-04（settings: forecast closing day 更新・singleton）, DEA-A1-05（**非空 stage の削除**＝deal を持つ stage を delete→server 拒否 vs 孤児化・frontend の無反応疑い） |
| A-2 一覧（検索/フィルタ/ソート/ページング/0件/大量） | DEA-A2-01（board フィルタ = show won/lost・show deleted）, DEA-A2-02（board 0件 EmptyState）, DEA-A2-03（audit from/to フィルタ＋invalidRange）。**検索・ソート・ページングの UI は board/一覧に無し**（kanban は全件列・audit は日付範囲のみ）を明記 |
| A-3 主要業務フロー（売りの導線） | DEA-A3-01（pipeline: 作成→DnD stage move→won→**invoice-handoff 実挙動**＝P-7 裏取り）, DEA-A3-02（audit CSV export 一巡＝証跡の売り・**日本語データの Excel 文字化け/BOM**）, DEA-A3-03（**lost 導線**＝DnD→lost stage・Lost 特別描画・weighted/forecast 除外・show won/lost OFF で消える） |
| A-4 ダッシュボード・集計値の整合 | DEA-A4-01（forecast strip の weighted/open/won ↔ board 各 column の client 集計の整合・**server 集計と client 集計の二重計算**を突く） |
| A-5 ナビ全リンク一巡 | DEA-A5-01（AppShell nav 全項目〔board/stages/users/audit/settings〕＋detail 往復＋mobile tabs） |
| A-6 デモ固有導線 | DEA-A6-01（/demo/standard: disposable org・admin seat・board 着地）, DEA-A6-02（/demo/standard 再訪・連打＝毎回新 org・mint レート制限 30/h の節度）, DEA-A6-03（リロードで session 消滅＝memory-only token・再訪要）。P-1 注記（SPA ルートでなく配信入口・/demo/guided は無し） |

### B. 異常系 — 入力
| 項目 | 該当シナリオ / 該当なし理由 |
|---|---|
| B-1 境界値 | DEA-B1-01（amount: 0/負/巨大〔円×100 の桁〕/小数）, DEA-B1-02（probability: 0/100/-1/101）, DEA-B1-03（stage sortOrder: 0/負・inline edit の silent guard） |
| B-2 空・必須欠落・空白のみ | DEA-B2-01（create-deal account 空/空白）, DEA-B2-02（user create email 空・password<8）, DEA-B2-03（stage label 空/空白） |
| B-3 型不正 | DEA-B3-01（login email 不正形式＝client 検証なし→server 挙動）, DEA-B3-02（user create email 不正形式＝client email 検証あり）, DEA-B3-03（date 欄に不正形式・native date widget 迂回） |
| B-4 多バイト・絵文字・RTL・HTML/スクリプト | DEA-B4-01（account/note に `<script>alert(1)</script>` → board/detail/timeline の表示エスケープ（audit は CSV のみ＝画面は該当なし・§3 batch 2b））, DEA-B4-02（絵文字・RTL・多バイト account/note） |
| B-5 過長入力 | DEA-B5-01（account/note の上限超・巨大貼付） |
| B-6 二重送信・連打 | DEA-B6-01（create/save/handoff/move の連打→pending disable・多重送信防止） |
| B-7 ファイル入出力 | **該当なし**: deal にファイル upload/添付機能が無い（フロント全域に file input・upload エンドポイント無し）。export は CSV のみ（B-8 で扱う） |
| B-8 エクスポート注入 | DEA-B8-01（audit の deal 名/note/field に `=`,`+`,`-`,`@`,`TAB`,`\r` 始まりの値を仕込み→audit CSV export のセルで式化しないか＝**NENE2 `Nene2\Export\CsvWriter`** の中和実挙動裏取り） |

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

### DEA-A1-05: 非空 stage の削除（孤児化 vs 拒否・frontend の無反応疑い）
- 分類: A-1 / 異常（**hub 査読追補・発見直結**）
- 前提: admin・`/stages`・**deal を1件以上持つ非 terminal stage** を用意（board で当該 stage に deal を作る）
- 手順: 1. 中に deal がある stage の Delete → confirm（「Delete this stage?」）
- 期待: server（`DeleteStageUseCase`）は `hasDeals` ガードで **`StageHasDealsException`（409 conflict）を投げ、削除しない＝deal は孤児化しない**（孤児化疑いは server 側で否定される想定）。ただし **stage feature の delete は成功/失敗トーストが無い（silent）**ため、**拒否時にユーザへ何の反応も出ない疑い**＝⚠️/🔴 候補（confirm→無反応で「消えたのか？」となる UX）。実挙動を発見として固定
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

### DEA-A3-02: audit CSV export 一巡（日本語データの Excel 文字化け/BOM）
- 分類: A-3 / 正常
- 前提: admin・`/audit`・操作履歴あり（**日本語の account 名/note を含む deal 操作を事前に作る**）
- 手順: 1. 範囲指定→Download 2. DL した CSV を **Excel（Windows/Mac）と表計算で開く** 3. 日本語セルの文字化けと BOM 有無を確認
- 期待: `audit-{from}_{to}.csv` が DL・success トースト・列が UI 表示と一致・操作が行として入る。**日本語が Excel で文字化けしない**（UTF-8 BOM 付与の有無で Excel の自動判定が変わる＝BOM 無しだと Excel が Shift_JIS 誤認で化ける可能性→営業直結。BOM 有無を実測し記録）
- 結果:
- 証拠:
- 発見:

### DEA-A3-03: lost 導線（DnD→lost・Lost 特別描画・forecast 除外・フィルタ）
- 分類: A-3 / 正常（**won だけ厚く lost を踏まないデモ致命穴の防止・hub 査読追補**）
- 前提: admin・board・lost stage あり
- 手順: 1. deal を DnD で **lost stage** へ移動 2. board 上の当該カードの描画（Lost 特別表示・`isLost`）3. board stat strip の weighted/forecast に lost が**含まれない**ことを確認 4. 「Show won/lost」を OFF にして lost カードが消えることを確認 5. detail を開き lost 状態表示
- 期待: 2. lost カードが won とは別の特別描画（グレーアウト/Lost バッジ等）3. weighted/pipeline forecast から lost が除外（terminal 扱い）4. show won/lost OFF で lost 列/カードが非表示 5. detail に lost 反映。**lost 導線が破綻/未描画なら🔴（デモで負け筋を見せられない）**
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

### DEA-B8-01: audit CSV インジェクション中和（NENE2 `Nene2\Export\CsvWriter` 裏取り）
- 分類: B-8 / 異常（営業リスク）
- 前提: admin
- 手順: 1. account/note に `=1+1`・`=HYPERLINK("http://evil","x")`・`+1`・`-1`・`@x`・TAB 始まり・`\r` 始まりの値を保存し、**さらに `PATCH` で更新する**（**`created` 行は field/before/after が空で CSV に載らない**＝update しないと audit に到達しない・§3 batch 2b の訂正）2. `/audit` で export→CSV を Excel/表計算で開く。stage 名（`stage_changed` 行の before/after）と actor email も同じ CSV 経路
- 期待: CSV セルで**式として評価されない**（`Nene2\Export\CsvWriter` が `FORMULA_TRIGGERS=['=','+','-','@',"\t","\r"]` 先頭一致で `'` prefix 中和・`sanitizeFormulas` default on）。全 trigger（`\r` 含む）で中和を確認。評価されたら🔴（営業直結）。※中和は NENE2 vendor 実装（app 側 #53 ではない）
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
- 前提: demo org A で得た token・別 org B の実在 deal id
- 手順: **他 org id の安全な取得法**＝別ブラウザコンテキスト（別プロファイル/incognito）で `/demo/standard` をもう一度開き **2つ目の disposable org B を mint**→B の deal id を控える（推測でなく実在 id で確実に検証）。1. org A の token/セッションで `/deals/<org-B-id>` を直叩き（API も直接）
- 期待: 404/403 で他 org データを返さない（テナント分離・情報漏えい無し）。org A の一覧/detail に B の deal が一切現れない
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
  - **batch 1（構造系・自動化 live Playwright）**: 2026-07-21・Deal リナ・Chromium(Playwright)・1280×900・`https://deal.ayane.co.jp/demo/standard`（生存 HTTP 200 実測）・本番デプロイ凍結不要。
- サマリ（batch 1 = 構造系11項目）: ✅ 8 / 🔴 0 / ⚠️ 3（うち2は**テスト手法起因**・下記）/ 残 §2 の異常系・security・race・TZ・handoff・CRUD 書込・多タブは **batch 2 以降で継続**。
- 🔴・⚠️ は**その場で再現手順を確定**させてから次へ進む（後から再現できない報告を作らない）。

### batch 1 結果（構造系・live 実測）

| ID | 結果 | 観察 |
|---|---|---|
| DEA-A6-01 | ✅ | `/demo/standard` → seat 経由で `/`（board）着地・topnav 表示・admin nav 全項目 `[Pipeline\|Stages\|Users\|Audit\|Settings]` |
| DEA-A5-01 | ⚠️（手法補正要）| nav 項目は admin 全5件を確認。ただし各ルートの**ハードナビ（full load）は `/login` へ**＝**メモリ専用トークンが full load で失われる**ため（下記 F-1 発見）。**nav 到達性はクライアント側クリックで batch 2 再測**（ハードナビ→login は仕様どおり） |
| DEA-C1-01 | ✅（batch1 で確証）| 上記の裏返し＝**公開デモは fail-closed（`VITE_REQUIRE_LOGIN=true`）**。トークン無し／full load で保護 URL 直叩き → `/login` へ確実に落ちる（情報露出なし） |
| DEA-D2-01 | ✅ | 未知 URL `/zzz-unknown-route` → 意匠付き 404（"The requested item was not found. … Back to pipeline"）・クラッシュ無し |
| DEA-E1-01 | ✅ | ThemeToggle 押下で `html[data-theme]` が変化（light↔dark 切替動作）・崩れ目視は batch 2 の全画面撮りで |
| DEA-E3-02 | ✅ | 言語スイッチャのラベルが endonym 固定 `[日本語 \| EN]`（判例19 の意図どおり） |
| DEA-E3-01 | ⚠️（弱アサーション）| lang 押下で first nav link テキスト不変と観測＝**要手動確認**（当該 nav 語が ja/en 同綴りの可能性・切替自体の可否を batch 2 で目視） |
| DEA-A6-03 | ✅ | board でリロード → `/login`（**トークンはメモリ専用**＝リロードでセッション消滅・再訪で新 org）。仕様どおり |
| DEA-F4-01(batch1) | ✅ | batch1 の全遷移で **console error 0 / ネットワーク 4xx・5xx 0** |

**batch 1 の主要観察（発見候補・hub 連携）**: 公開デモは **fail-closed**（保護 URL の full load 直叩き→login）＝C-1 は良好側で確証。**トークンがメモリ専用**のため full page load を挟む操作（ハードナビ・リロード）は毎回 login に戻る＝deep-link（D-2）や新規タブでの保護 URL は「一旦 login」挙動になる（クライアント側 SPA 遷移では保持）。営業導線上の含意は F-1 で評価。

### batch 2a 結果（security 先行 2件・live 実測 2026-07-29）

**実行条件**: 2026-07-29・Deal リナ・Chromium(Playwright 1.61.1)・`https://deal.ayane.co.jp/demo/standard` の使い捨て org 内・座席 mint は各ラウンド1回（連打節度）・API 直叩き併用（打鍵経路は §末尾の「実行方法の但し書き」参照）。hub 裁定 07-29 = **公開デモが本番稼働中のため security 2件（B-8/B-4）を C5 drain より先に抜く**。

| ID | 結果 | 観察 |
|---|---|---|
| DEA-B8-01 | ✅ | **全7トリガで中和を確認**（`=` `+` `-` `@` TAB CR ＋ `=HYPERLINK("http://evil.example","x")`）。audit CSV の該当セルは**すべて先頭 `'` prefix**で出力。CSV を RFC4180 パースし**セル完全一致**で判定（下記「⚠️ 判定手法の訂正」参照）。中和は NENE2 vendor `Nene2\Export\CsvWriter`（`FORMULA_TRIGGERS = ['=','+','-','@',"\t","\r"]`・`vendor/hideyukimori/nene2/src/Export/CsvWriter.php:39,138-140`）。式評価の兆候なし＝**営業リスク無し** |
| DEA-B4-01 | ✅（board / stage 名）<br>⚠️（detail・timeline・audit 画面は未到達）| 保存した markup が**全て文字列としてエスケープ表示**。deal 名 `<b>bold</b>` / `<img src=x>`、stage 名 `<i>stg</i>` のいずれも**リテラル文字として可視**。DOM 注入 0（payload 由来の `<b>`/`<i>` 要素 0・`img[src=x]` 0・`alert(` を含む `<script>` 0）・**dialog 発火 0**・pageerror 0。`&lt;`/`&amp;` の**二重デコードも無し**、引用符・アンパサンドも保持 |

#### 🔴 仕様書の訂正 — B-8 のベクタは deal 名/note ではなく **stage 名**

§2 の DEA-B8-01 手順は「account/note に trigger 値を仕込む」でしたが、**実測の結果この経路では audit CSV に到達しません**。

audit CSV の実カラムは `timestamp,actor,action,deal_id,field,before,after` で、deal 作成行は
`"2026-07-29 10:51:49",demo-admin@…,created,01KYP…,,,` と **field/before/after が空**＝`account_label`・`note` は **CSV に一切出力されない**。

したがって **ユーザ入力が CSV セルへ届く経路は次の2つ**です:

1. **`before`/`after` = stage 名**（`stage_changed` 行）— stage は自由文（label maxLength 64・#36 で rename/add 可）＝**これが真のインジェクション面**
2. **`actor` = ユーザの email**（email 形式検証あり＝トリガ文字は先頭に置きにくい）

今回は (1) で実測し、**7トリガ全て中和**を確認しました。**§2 の DEA-B8-01 手順文をこの経路に差し替える必要があります**（次回改訂で反映）。

#### 🟠 発見 — 端 WAF が XSS ペイロードを app 到達前に 403 で遮断（本番のみ）

`<script>…</script>` / `onerror=` / `"><img …` を含む値は、**アプリに届く前に共用ホスティング（HETEML）の WAF が 403 を返します**。本文は日本語の非ブランド HTML（`<TITLE>閲覧できません (Forbidden access)</TITLE>`）で、**JSON でも RFC9457 Problem Details でもありません**。

| ペイロード | 結果 |
|---|---|
| `plain` / `<b>bold</b>` / `<img src=x>` | 201（app 到達・**エスケープ確認できた**） |
| `<img src=x onerror=alert(1)>` | **403（端 WAF・app 未到達）** |
| `<script>alert(1)</script>` | **403（端 WAF・app 未到達）** |
| `"><img src=x onerror=alert(1)>` | **403（端 WAF・app 未到達）** |
| `<svg/onload=1>` | **403（端 WAF・app 未到達）** |

含意2点:
- **セキュリティ的にはむしろ良好側**（多層防御）。ただし **app 自身の防御が本番トラフィックで検証できない**＝「WAF に守られている」状態と「app が安全」は別。今回は WAF を通過する markup（`<b>`・`<img src=x>`）で **app 側の React 既定エスケープが効いていることを実測**したので、同じ描画経路を通る `onerror` 系も同様にエスケープされる、と判断できます（**推論**・本番での直接実測は WAF により不可）。
- **UX 面は要確認（batch 2 残余へ）**: SPA は 403 の HTML を JSON として解釈できないため、利用者が商談名に `<` を含む文字列（例: 社名の記号や引用符混じり）を入れた際に**何のエラーか分からないトーストになる**可能性があります。**実 UI 打鍵での確認が必要**（今回は API 直叩きのため未確認）。

#### ⚠️ 判定手法の訂正（自己申告・2回とも当方の測定ミス）

初回の判定は**2つとも無効**で、やり直しています。同型の誤りは他艦でも起きるので手順ごと残します。

1. **部分文字列マッチの衝突** — `+1 QA…` を `includes()` で探したところ、`=1+1 QA…` の中の `+1 QA…` にヒットして別セルを「中和済み」と誤判定していました。**CSV を実際にパースし、セル文字列の完全一致（`cell === "'" + payload`）で判定**するよう修正。
2. **ハードナビでトークンを失い board が描画されていなかった** — `page.goto('/')` で再訪した結果、batch 1 で判明済みの **F-1（トークンはメモリ専用）** をそのまま踏み、board が `/login` 相当で payload が 0 件。「注入なし」に見えていたのは**そもそも何も描画されていなかった**ためで、**偽の ✅ でした**。SPA セッション内のクライアント遷移のみに修正。
3. さらに **TanStack Query の `staleTime: 30_000`**（`frontend/src/app/providers.tsx:22`）により、作成直後の再取得はキャッシュに阻まれます。**35 秒待ってから**クライアント遷移して初めて payload が board に現れました。ここを待たずに「表示されない＝注入なし」と読むと、また偽 ✅ になります。

> **教訓**: 「危険な文字列が画面に出てこない」は ✅ の証拠になりません。**まず payload が確かに描画されていること**を確認し、その上で**要素として解釈されていないこと**を見る、の2段で判定する。

#### 実行方法の但し書き（打鍵純度）

本 batch は**データ投入を API 直叩き**（座席から捕捉した bearer で `POST /api/v1/deals`・`/api/v1/stages`・`/stage-change`）、**検証を実 UI の DOM 実測**で行いました。理由は 7トリガ×複数 venue を UI 手入力で回すと座席 mint とレート制限に触れるため。**表示側（＝本 batch の判定対象）は実ブラウザの実 DOM で見ています**。ただし上記のとおり **入力フォーム経由の挙動（WAF 403 時のトースト文言）は未確認**なので、batch 2 残余で実 UI 打鍵を行います。

### batch 2b 追補（2a の残 venue 到達＋2a 記述の訂正・2026-07-29 夜）

**実行条件**: 2026-07-29 23時台・Deal リナ（別セッション・2a との重複着手を後から検知＝末尾「重複の経緯」）・Chromium(Playwright 1.61.1)。**B-4 は本番ではなくローカル**（`docker compose` app:8110 ＋ Vite:5187・`tools/seed-demo.php` seed 済み）で実施。理由は下記 🔴 の方法論。B-8 は本番デモ org（`https://deal.ayane.co.jp/demo/standard`）で実施。

| ID | 結果 | 観察 |
|---|---|---|
| DEA-B8-01 | ✅（**陽性対照つき**） | 8トリガ（`=1+1` / `=HYPERLINK(...)` / `+1+1` / `-1+1` / `@SUM(1+1)` / TAB / CR / `=cmd\|'/c calc'!A1`）を **deal の `account_label`・`note`** に仕込み `PATCH` → audit CSV の当該 32 セル**すべてに先頭 `'` prefix**。**陽性対照**: 先頭が安全な文字の CONTROL 値（`CONTROL safe 1+1`）には **prefix が付かない**（0件）＝無条件付与ではなく**先頭一致で選択的に効く本物の中和**であることを確認。CSV は **UTF-8 BOM 付き**（`ef bb bf`）＝Excel 文字化け対策も同時に確認（A3-02 の一部を前倒し実測） |
| DEA-B4-01 | ✅（**detail 4画面に到達**・2a の未到達を解消） | payload 4種（`<script>alert(1)</script>` / `x"><img src=x onerror=alert(1)>` / `javascript:alert(3)` ＋ `<svg onload=alert(4)>` note / 多バイト）を投入し、**board ＋ deal detail 4画面**を実 DOM 実測。全画面で `#root script`=0・`img[onerror]`=0・`svg[onload]`=0・`a[href^=javascript:]`=0・**dialog 発火 0**・pageerror 0。`#root` の innerHTML 側で **`&lt;script&gt;` にエスケープ**されていることを直接確認 |
| DEA-B4-02 | ✅ | 絵文字 😀・RTL アラビア語・全角が board / detail で**文字化けせず保持**・レイアウト破綻なし |

**静的裏取り**: frontend 全域に `dangerouslySetInnerHTML` / `.innerHTML` / `outerHTML` / `eval(` / `new Function(` / `document.write` が **1件も無い**（`grep -rE` 実測）。エスケープは React 既定のみに依存＝sink が存在しない構造。

#### 🔴 2a 記述の訂正 — `account_label`/`note` は **CSV に出力される**（update 経路）

2a は「deal 作成行は field/before/after が空＝`account_label`・`note` は **CSV に一切出力されない**／ユーザ入力が CSV セルへ届くのは stage 名と actor email の2経路」と記録していますが、**これは `created` 行だけを見た結論で、誤りです**。

deal を **`PATCH /deals/{id}` で更新**すると、変更フィールドが1行1フィールドで CSV に出ます（実測・本番デモ org）:

```
"2026-07-29 14:17:53",demo-admin@…,updated,01KYQ3S9KY…,account_label,"'=1+1 B8-01 equals","'=1+1 UPDATED"
"2026-07-29 14:17:53",demo-admin@…,updated,01KYQ3S9KY…,note,"'=1+1 NOTE","'=1+1 NOTEUPD"
```

したがって **インジェクション面は 2a の想定より広く**、`before`/`after` には **stage 名だけでなく `account_label`・`note` など更新対象フィールドの値**が届きます。**中和は全経路で効いているので結論（✅・営業リスク無し）は変わりません**が、「真のインジェクション面は stage 名」という記述を残すと、将来 `CsvWriter` の `sanitizeFormulas` を触ったときに影響範囲を過小評価します。§2 の DEA-B8-01 手順文は「**値を保存**」ではなく「**値を保存し、さらに `PATCH` で更新する**」に差し替えが要ります（`created` 行には載らないため）。

> 一般則: **「作成行に出ない」は「その項目は出ない」ではない**。監査ログは通常 create と update で記録形が違うので、両方の経路を踏んでから出力面を確定すること。

#### 🔴 方法論 — **B-4 は公開デモでは検証できない**（WAF が陽性対照を潰す）

2a は「WAF を通過する markup（`<b>`・`<img src=x>`）で React 既定エスケープを実測したので、`onerror` 系も同様と**判断できます（推論）**」としていますが、**推論を実測に格上げできます**。端 WAF が無いローカルで打鍵すればよい。

本追補では **ローカル（WAF 無し）** で `<script>`・`onerror`・`svg onload` を**実際に app へ到達させ**、実 DOM で無害化を確認しました（上表）。公開デモでの再実測は不要です。

| 環境 | `<script>alert(1)</script>` の到達 | B-4 の判定価値 |
|---|---|---|
| 公開デモ（本番） | **403・app 未到達**（端 WAF） | ❌ 「XSS が出ない」を見ても**アプリの防御の証明にならない**＝偽の合格 |
| ローカル compose | **201・app 到達** | ✅ アプリ層の防御を直接判定できる |

**§0 のスコープ規定「対象は公開デモ環境のみ」は B-4 系と衝突します。** インジェクション系は「payload が app に届いていること」が判定の前提なので、**WAF 無し環境で実施**と明記すべきです（本追補で実施済み）。WAF 403 のフォーム UX 文言（2a の 🟠 残件）だけは本番固有なので公開デモ側に残ります。

なお **WAF の挙動は 2a と一致**を再確認: `<script>` / `onerror` / `"><img` は 403（非 JSON の日本語 HTML）、**正当な `株式会社テスト <大阪支店> & "quote"` は 201 で通過**（board 123 行・Issue #177 の実測と一致）。

#### 🟠 audit 画面は B-4 の venue として **該当なし**（未到達ではない）

2a は「audit 画面は未到達」と残していますが、実測の結果 **`/audit` は CSV エクスポート専用画面**で、監査行の**一覧表示そのものが存在しません**（画面要素は日付範囲ピッカー・記録対象の説明・CSV カラム説明・Download ボタンのみ）。したがって B-4 の表示 venue としては **該当なし**が正しく、CSV 側は DEA-B8-01 で検証済みです。§1 の B-4 venue 列挙（「audit CSV/画面」）は「audit **CSV**」のみに訂正が要ります。

同様に **activity timeline は deal detail 内**にあり、detail 4画面の実測に含まれています（別 venue ではない）。

#### 判定手法（2a の教訓を継承し、さらに1段追加）

2a の4前提（クライアント遷移のみ／staleTime 待ち／2段判定／CSV セル完全一致）はすべて踏襲。本追補で**ハードナビによる偽 ✅ を実際に再現**しました（`page.goto('/')` 後 marker 0件・dialog 0 で一見 PASS）。2a の警告どおりだったので、**プローブ側に「陽性対照を満たさなければ PASS を出さない」ガードを組み込み**ました:

1. **検査器の陽性対照** — `window.alert()` を意図的に起こし、dialog ハンドラが捕まえることを確認してから本測定に入る（捕まえられなければ `exit 2` で中断）。
2. **到達の陽性対照** — payload マーカーが実際に描画されていること（`reached`）を PASS の**必要条件**にする。マーカー0件なら `CHECK` に落ちて ✅ にならない。
3. **中和の陽性対照**（B-8） — 中和されるべきでない CONTROL 値に prefix が**付かない**ことを確認。全セルに無条件付与なら中和の証明にならないため。

> 一般則（フリート共有済み・`_work/advice.md`）: **「無いこと」の検証には陽性対照が要る。** 検査器が生きていること・対象が検査範囲に入っていることを先に示さなければ、`0 件` は「安全」ではなく「測れていない」と読むべき。

#### 重複の経緯（プロセスの記録）

本追補は 2a（同日 20:01・コミット `dd8c932`）と**同じ2シナリオを重複実行**しました。原因は着手前の確認漏れです: `board.txt` 112 行が「**07-22 以降 未着手7日**」のままで、PR #168 の `updatedAt`（同日 11:01Z＝20:01 JST）を取得していたのに突き合わせませんでした。**board の記述より PR/ブランチの最終更新が新しい場合、後者が正**。着手前に対象ブランチの `git log -1` を見ていれば防げました。

重複の結果として 2a の空白（detail venue）と誤り（CSV 出力経路）が埋まったため成果は無駄ではありませんが、**同じ時間で C5 drain を進められた**ことも事実です。以後、batch 着手前に ①対象ブランチの最新コミット ②PR の updatedAt を確認します。

### 実行メモ（🔴/⚠️ の再現手順・batch 2 以降）

- **batch 2 予定（要慎重・一部手動）**: A-1 CRUD 書込（disposable org 内）／**A-3 invoice-handoff 実挙動＝LP 連携裏取り**（invoice リナ実測「Deal 引き継ぎ未実装」と一致するか）／**B-8 audit CSV injection**（`=HYPERLINK` 実注入→NENE2 `Nene2\Export\CsvWriter` 中和裏取り）／**A3-03 lost 導線**／**A1-05 非空 stage 削除**／**A3-02 CSV 日本語 BOM**／B-1/B-2/B-3 入力検証／**B-4 XSS 表示エスケープ**／**DEA-D3-01〜04 楽観ロック/連打 race**（要2タブ・operator ユーザ作成）／**E-4 TZ off-by-one**（timezoneId で UTC/JST 両撮り＝hub 手法）／E-2 レスポンシブ／E-5 金額書式。
- A-5 nav 到達性・E-3-01 lang 切替は batch 2 でクライアント側クリック法にて再測（batch1 の⚠️2件はハードナビ／弱アサーションの手法起因で、機能不具合の疑いではない）。
- **batch 2a（07-29）で消化済**: B-8 ✅ / B-4 ✅（board・stage 名）。**残**: B-4 の detail・timeline・audit 画面の venue（今回 UI 導線を特定できず未到達）、および **WAF 403 時のフォーム UX 文言**（新規・上記 🟠）。
- **batch 2b（07-29 夜・追補）で消化済**: B-4 の **detail 4画面 ✅**（timeline は detail 内・audit 画面は**該当なし**と確定）／B-8 を **`account_label`・`note` の update 経路でも ✅**（陽性対照つき）／B-4 を **ローカル（WAF 無し）で実測に格上げ**（2a の「推論」を解消）。**残**: **WAF 403 時のフォーム UX 文言のみ**（本番固有・実 UI 打鍵・Issue #177 と同件）。
- **batch 2 の実行時に必ず効かせる前提**（2a で判明）: ①クライアント遷移のみ（ハードナビ＝トークン喪失） ②書込後の再取得は **staleTime 30 秒**待ち ③判定は「描画されている」→「解釈されていない」の2段 ④CSV 判定はパースしてセル完全一致。
