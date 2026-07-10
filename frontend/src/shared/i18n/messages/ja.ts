/**
 * Japanese message catalog — the authoritative source of truth (ADR 0004).
 * Add new keys here first; `en.ts` mirrors the same `MessageCatalog` shape.
 */
export interface MessageCatalog {
  'app.title': string
  'app.subtitle': string

  'common.actions.create': string
  'common.actions.cancel': string
  'common.actions.retry': string

  'common.error.unauthorized': string
  'common.error.forbidden': string
  'common.error.notFound': string
  'common.error.conflict': string
  'common.error.validation': string
  'common.error.rateLimit': string
  'common.error.serverError': string
  'common.error.unknown': string

  'board.loading': string
  'board.error.title': string
  'board.empty.title': string
  'board.empty.description': string
  'board.column.summary': string
  'board.column.empty': string
  'board.show': string
  'board.showTerminal': string
  'board.showDeleted': string
  'board.deletedBadge': string
  'board.restore': string
  'board.dragHint': string

  'toast.region': string
  'toast.dismiss': string
  'toast.saved.title': string
  'toast.saved.sub': string
  'toast.moved.title': string
  'toast.move.error.title': string
  'toast.deleted.title': string
  'toast.delete.error.title': string
  'toast.restored.title': string
  'toast.restore.error.title': string

  'forecast.title': string
  'forecast.openDealCount': string
  'forecast.pipelineTotal': string
  'forecast.weightedTotal': string
  'forecast.delta.allStages': string
  'forecast.delta.weighted': string
  'forecast.delta.wonCount': string

  'deal.create.open': string
  'deal.create.title': string
  'deal.create.submit': string
  'deal.create.error': string
  'deal.field.accountLabel': string
  'deal.field.amount': string
  'deal.field.stage': string
  'deal.field.probability': string
  'deal.field.expectedCloseDate': string
  'deal.field.owner': string
  'deal.field.note': string
  'deal.validation.accountLabelRequired': string
  'deal.validation.amountPositive': string
  'deal.validation.probabilityRange': string

  'common.actions.back': string
  'common.actions.save': string
  'common.actions.edit': string

  'deal.open.detail': string
  'detail.loading': string
  'detail.error.title': string
  'detail.notFound': string
  'detail.edit.title': string
  'detail.edit.error': string
  'detail.note.empty': string
  'detail.owner.label': string
  'detail.owner.empty': string
  'detail.delete.label': string
  'detail.delete.confirm': string
  'detail.delete.hint': string
  'detail.activity.title': string
  'detail.activity.sub': string
  'detail.activity.expand': string
  'detail.activity.empty': string
  'detail.activity.created': string
  'detail.activity.updated': string
  'detail.activity.stageChanged': string
  'detail.activity.deleted': string
  'detail.activity.restored': string
  'detail.activity.handoff': string

  'handoff.title': string
  'handoff.description': string
  'handoff.send': string
  'handoff.error': string
  'handoff.linked': string
  'handoff.clientId': string
  'handoff.quoteId': string

  'login.title': string
  'login.subtitle': string
  'login.email': string
  'login.password': string
  'login.submit': string
  'login.failed': string
  'login.validation.emailRequired': string
  'login.validation.passwordRequired': string

  'locale.label': string

  // Calm redesign — shell, nav, hero
  'nav.board': string
  'nav.stages': string
  'nav.users': string
  'nav.audit': string
  'nav.settings': string
  'settings.title': string
  'settings.subtitle': string
  'settings.forecast.title': string
  'settings.closingDay.label': string
  'settings.closingDay.monthEnd': string
  'settings.closingDay.hint': string
  'settings.error': string
  'shell.account': string

  'audit.title': string
  'audit.subtitle': string
  'audit.adminOnly': string
  'audit.rangeTitle': string
  'audit.from': string
  'audit.to': string
  'audit.recorded': string
  'audit.chip.created': string
  'audit.chip.edited': string
  'audit.chip.moved': string
  'audit.chip.deleted': string
  'audit.chip.restored': string
  'audit.chip.handoff': string
  'audit.colsLabel': string
  'audit.colsVal': string
  'audit.download': string
  'audit.downloading': string
  'audit.invalidRange': string
  'audit.hint': string
  'toast.audit.success.title': string
  'toast.audit.error.title': string
  'shell.settingsTitle': string
  'shell.signout': string
  'shell.theme': string
  'shell.themeLight': string
  'shell.themeDark': string
  'shell.lang': string
  'board.heading': string
  'board.subtitle': string
  'forecast.wonThisMonth': string
  'login.heroEyebrow': string
  'login.heroTitle': string
  'login.heroBody': string
  'login.secure': string
  'login.kpiActive': string
  'login.kpiWeighted': string
  'login.kpiUptime': string
  'detail.back': string
  'detail.signed': string
  'handoff.hint': string
  'stages.editing': string
  'users.you': string
  'users.detail.title': string
  'users.detail.idLabel': string
  'users.detail.tap': string
  'users.delete.label': string

  'users.title': string
  'users.subtitle': string
  'users.loading': string
  'users.error.title': string
  'users.empty.title': string
  'users.empty.description': string
  'users.create.open': string
  'users.create.title': string
  'users.create.submit': string
  'users.create.error': string
  'users.create.success': string
  'users.field.email': string
  'users.field.password': string
  'users.field.role': string
  'users.role.admin': string
  'users.role.operator': string
  'users.delete.confirm': string
  'users.delete.error': string
  'users.edit.title': string
  'users.edit.error': string
  'users.validation.emailRequired': string
  'users.validation.passwordMin': string
  'users.validation.roleRequired': string

  'common.actions.edit': string

  'stages.title': string
  'stages.subtitle': string
  'stages.loading': string
  'stages.error.title': string
  'stages.empty.title': string
  'stages.empty.description': string
  'stages.create.open': string
  'stages.create.title': string
  'stages.create.submit': string
  'stages.create.error': string
  'stages.field.label': string
  'stages.field.sortOrder': string
  'stages.badge.terminal': string
  'stages.badge.won': string
  'stages.delete.confirm': string
  'stages.delete.error': string
  'stages.delete.forbidden': string
  'stages.edit.save': string
  'stages.edit.error': string
  'stages.validation.labelRequired': string
  'stages.validation.sortOrderNonNeg': string
}

export const ja: MessageCatalog = {
  'app.title': 'NeNe Deal',
  'app.subtitle': '営業パイプライン',

  'common.actions.create': '作成',
  'common.actions.cancel': 'キャンセル',
  'common.actions.retry': '再試行',

  'common.error.unauthorized': '認証が必要です。',
  'common.error.forbidden': 'この操作を行う権限がありません。',
  'common.error.notFound': '対象が見つかりませんでした。',
  'common.error.conflict': '競合が発生しました。',
  'common.error.validation': '入力内容を確認してください。',
  'common.error.rateLimit': 'リクエストが多すぎます。しばらくお待ちください。',
  'common.error.serverError': 'サーバーエラーが発生しました。',
  'common.error.unknown': '予期しないエラーが発生しました。',

  'board.loading': 'ボードを読み込んでいます…',
  'board.error.title': 'ボードを読み込めませんでした',
  'board.empty.title': 'まだステージがありません',
  'board.empty.description': 'パイプラインのステージが設定されていません。',
  'board.column.summary': '{{count}} 件 · 加重 {{weighted}}',
  'board.column.empty': 'ディールなし',
  'board.show': '表示',
  'board.showTerminal': '受注・失注も表示',
  'board.showDeleted': '削除済みも表示',
  'board.deletedBadge': '削除済み',
  'board.restore': '復元',
  'board.dragHint': 'ドラッグでステージを移動',

  'toast.region': '通知',
  'toast.dismiss': '閉じる',
  'toast.saved.title': '保存しました',
  'toast.saved.sub': '変更を反映しました',
  'toast.moved.title': 'ステージを移動しました',
  'toast.move.error.title': '移動できませんでした',
  'toast.deleted.title': '削除しました',
  'toast.delete.error.title': '削除できませんでした',
  'toast.restored.title': '復元しました',
  'toast.restore.error.title': '復元できませんでした',

  'forecast.title': '今月の着地見込み',
  'forecast.openDealCount': '進行中ディール',
  'forecast.pipelineTotal': 'パイプライン合計',
  'forecast.weightedTotal': '加重見込み',
  'forecast.delta.allStages': '全ステージ',
  'forecast.delta.weighted': '確度で按分',
  'forecast.delta.wonCount': '受注{{count}}件',

  'deal.create.open': 'ディールを追加',
  'deal.create.title': '新規ディール',
  'deal.create.submit': '作成する',
  'deal.create.error': 'ディールを作成できませんでした。',
  'deal.field.accountLabel': '取引先名',
  'deal.field.amount': '金額（円）',
  'deal.field.stage': 'ステージ',
  'deal.field.probability': '確度（％）',
  'deal.field.expectedCloseDate': '完了予定日',
  'deal.field.owner': '担当者',
  'deal.field.note': 'メモ',
  'deal.validation.accountLabelRequired': '取引先名を入力してください。',
  'deal.validation.amountPositive': '金額は0以上の整数（円）で入力してください。',
  'deal.validation.probabilityRange': '確度は0〜100で入力してください。',

  'common.actions.back': '戻る',
  'common.actions.save': '保存',
  'common.actions.edit': '編集',

  'deal.open.detail': '詳細',
  'detail.loading': 'ディールを読み込んでいます…',
  'detail.error.title': 'ディールを読み込めませんでした',
  'detail.notFound': 'ディールが見つかりませんでした。',
  'detail.edit.title': 'ディールを編集',
  'detail.edit.error': 'ディールを更新できませんでした。',
  'detail.note.empty': '（メモなし）',
  'detail.owner.label': '担当者',
  'detail.owner.empty': '担当者なし',
  'detail.delete.label': 'このディールを削除',
  'detail.delete.confirm': 'このディールを削除しますか？（あとで復元できます）',
  'detail.delete.hint': '論理削除です。30日以内であればパイプラインから復元できます。',
  'detail.activity.title': '変更履歴',
  'detail.activity.sub': '監査用のアクティビティログ（誰が・いつ・何を）',
  'detail.activity.expand': '中間の{{count}}件を表示',
  'detail.activity.empty': '履歴はまだありません',
  'detail.activity.created': '作成しました',
  'detail.activity.updated': '内容を変更しました',
  'detail.activity.stageChanged': 'ステージを移動しました',
  'detail.activity.deleted': '削除しました',
  'detail.activity.restored': '復元しました',
  'detail.activity.handoff': 'Invoice へ引き継ぎました',

  'handoff.title': 'NeNe Invoice へ引き渡し',
  'handoff.description': '受注したディールを Invoice に下書きの顧客・見積として送ります。',
  'handoff.send': 'Invoice へ送信',
  'handoff.error': 'Invoice への引き渡しに失敗しました。',
  'handoff.linked': 'Invoice 連携済み',
  'handoff.clientId': 'Invoice 顧客ID',
  'handoff.quoteId': 'Invoice 見積ID',

  'login.title': 'ログイン',
  'login.subtitle': 'NeNe Deal にサインインしてください。',
  'login.email': 'メールアドレス',
  'login.password': 'パスワード',
  'login.submit': 'ログイン',
  'login.failed': 'メールアドレスまたはパスワードが正しくありません。',
  'login.validation.emailRequired': 'メールアドレスを入力してください。',
  'login.validation.passwordRequired': 'パスワードを入力してください。',

  'locale.label': '言語',

  // Calm redesign — shell, nav, hero
  'nav.board': 'パイプライン',
  'nav.stages': 'ステージ管理',
  'nav.users': 'ユーザー管理',
  'nav.audit': '監査ログ',
  'nav.settings': '設定',
  'settings.title': '設定',
  'settings.subtitle': '組織のフォーキャスト設定を管理します。',
  'settings.forecast.title': 'フォーキャスト',
  'settings.closingDay.label': '締め日（集計期間）',
  'settings.closingDay.monthEnd': '月末（暦月）',
  'settings.closingDay.hint':
    '営業目標の集計期間の締め日です。例: 20 を選ぶと「前月21日〜当月20日」で集計します。請求の締め日（NeNe Invoice 側）とは別物です。',
  'settings.error': '設定を更新できませんでした。',
  'audit.title': '監査ログのエクスポート',
  'audit.subtitle': '期間を指定して、変更履歴をCSVでダウンロードします。',
  'audit.adminOnly': '管理者のみ',
  'audit.rangeTitle': '期間を指定',
  'audit.from': '開始日',
  'audit.to': '終了日',
  'audit.recorded': '記録される操作',
  'audit.chip.created': '商談を作成',
  'audit.chip.edited': '内容を編集',
  'audit.chip.moved': 'ステージを移動',
  'audit.chip.deleted': '削除済み',
  'audit.chip.restored': '復元',
  'audit.chip.handoff': 'Invoice へ引き継ぎ',
  'audit.colsLabel': 'CSV の列',
  'audit.colsVal': 'timestamp, actor, action, deal_id, field, before, after',
  'audit.download': 'CSVをダウンロード',
  'audit.downloading': '生成中…',
  'audit.invalidRange': '開始日は終了日より前にしてください。',
  'audit.hint':
    '作成・編集・ステージ移動・削除・復元・引き継ぎを、実行者と日時付きで記録しています。',
  'toast.audit.success.title': 'CSVをダウンロードしました',
  'toast.audit.error.title': 'エクスポートに失敗しました',
  'shell.account': 'アカウント',
  'shell.settingsTitle': 'アカウント・設定',
  'shell.signout': 'サインアウト',
  'shell.theme': '表示モード',
  'shell.themeLight': 'ライト',
  'shell.themeDark': 'ダーク',
  'shell.lang': '言語',
  'board.heading': 'パイプライン',
  'board.subtitle': '{{month}} · 全{{count}}件の進行中商談',
  'forecast.wonThisMonth': '今月の受注',
  'login.heroEyebrow': 'SALES PIPELINE',
  'login.heroTitle': '日々の商談を、確かな数字で見渡す。',
  'login.heroBody':
    '軽量な B2B 商談パイプライン。カンバン管理・売上予測・受注後の請求引き継ぎまでを一気通貫で。',
  'login.secure': '通信は暗号化され、安全に保護されています',
  'login.kpiActive': '進行中',
  'login.kpiWeighted': '加重予測',
  'login.kpiUptime': '稼働率',
  'detail.back': 'パイプラインへ戻る',
  'detail.signed': '署名日',
  'handoff.hint':
    '未連携の場合は「Invoice へ送信」ボタンが表示されます（連携後はこの状態に切り替わります）。',
  'stages.editing': '編集中',
  'users.you': 'あなた',
  'users.detail.title': 'ユーザー詳細',
  'users.detail.idLabel': 'ユーザーID',
  'users.detail.tap': 'タップで詳細',
  'users.delete.label': 'このユーザーを削除',

  'users.title': 'ユーザー管理',
  'users.subtitle': 'オペレーターアカウントを管理します。',
  'users.loading': 'ユーザーを読み込んでいます…',
  'users.error.title': 'ユーザーを読み込めませんでした',
  'users.empty.title': 'ユーザーがいません',
  'users.empty.description': '最初のオペレーターを作成してください。',
  'users.create.open': 'ユーザーを追加',
  'users.create.title': '新しいユーザー',
  'users.create.submit': '作成',
  'users.create.error': 'ユーザーを作成できませんでした。',
  'users.create.success': 'ユーザーを作成しました。',
  'users.field.email': 'メールアドレス',
  'users.field.password': 'パスワード',
  'users.field.role': 'ロール',
  'users.role.admin': '管理者',
  'users.role.operator': 'オペレーター',
  'users.delete.confirm': 'このユーザーを削除しますか？',
  'users.delete.error': 'ユーザーを削除できませんでした。',
  'users.edit.title': 'ロールを変更',
  'users.edit.error': 'ユーザーを更新できませんでした。',
  'users.validation.emailRequired': 'メールアドレスを入力してください。',
  'users.validation.passwordMin': 'パスワードは8文字以上で入力してください。',
  'users.validation.roleRequired': 'ロールを選択してください。',

  'stages.title': 'ステージ管理',
  'stages.subtitle': 'パイプラインのステージを設定します。',
  'stages.loading': 'ステージを読み込んでいます…',
  'stages.error.title': 'ステージを読み込めませんでした',
  'stages.empty.title': 'ステージがありません',
  'stages.empty.description': '最初のステージを作成してください。',
  'stages.create.open': 'ステージを追加',
  'stages.create.title': '新しいステージ',
  'stages.create.submit': '作成',
  'stages.create.error': 'ステージを作成できませんでした。',
  'stages.field.label': 'ラベル',
  'stages.field.sortOrder': '表示順',
  'stages.badge.terminal': '終了',
  'stages.badge.won': '受注',
  'stages.delete.confirm': 'このステージを削除しますか？',
  'stages.delete.error': 'ステージを削除できませんでした。',
  'stages.delete.forbidden': '終了ステージまたはディールのあるステージは削除できません。',
  'stages.edit.save': '保存',
  'stages.edit.error': 'ステージを更新できませんでした。',
  'stages.validation.labelRequired': 'ラベルを入力してください。',
  'stages.validation.sortOrderNonNeg': '表示順は0以上で入力してください。',
}
