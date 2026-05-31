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

  'forecast.title': string
  'forecast.openDealCount': string
  'forecast.pipelineTotal': string
  'forecast.weightedTotal': string

  'deal.create.open': string
  'deal.create.title': string
  'deal.create.submit': string
  'deal.create.error': string
  'deal.field.accountLabel': string
  'deal.field.amount': string
  'deal.field.stage': string
  'deal.field.probability': string
  'deal.field.note': string
  'deal.validation.accountLabelRequired': string
  'deal.validation.amountPositive': string
  'deal.validation.probabilityRange': string

  'common.actions.back': string
  'common.actions.save': string

  'deal.open.detail': string
  'detail.loading': string
  'detail.error.title': string
  'detail.notFound': string
  'detail.edit.title': string
  'detail.edit.error': string
  'detail.note.empty': string

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

  'forecast.title': '今月の着地見込み',
  'forecast.openDealCount': '進行中ディール',
  'forecast.pipelineTotal': 'パイプライン合計',
  'forecast.weightedTotal': '加重見込み',

  'deal.create.open': 'ディールを追加',
  'deal.create.title': '新規ディール',
  'deal.create.submit': '作成する',
  'deal.create.error': 'ディールを作成できませんでした。',
  'deal.field.accountLabel': '取引先名',
  'deal.field.amount': '金額（円）',
  'deal.field.stage': 'ステージ',
  'deal.field.probability': '確度（％）',
  'deal.field.note': 'メモ',
  'deal.validation.accountLabelRequired': '取引先名を入力してください。',
  'deal.validation.amountPositive': '金額は0以上で入力してください。',
  'deal.validation.probabilityRange': '確度は0〜100で入力してください。',

  'common.actions.back': '戻る',
  'common.actions.save': '保存',

  'deal.open.detail': '詳細',
  'detail.loading': 'ディールを読み込んでいます…',
  'detail.error.title': 'ディールを読み込めませんでした',
  'detail.notFound': 'ディールが見つかりませんでした。',
  'detail.edit.title': 'ディールを編集',
  'detail.edit.error': 'ディールを更新できませんでした。',
  'detail.note.empty': '（メモなし）',

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
}
