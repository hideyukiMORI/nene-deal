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
  'deal.validation.accountLabelRequired': string
  'deal.validation.amountPositive': string
  'deal.validation.probabilityRange': string

  'locale.label': string
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
  'deal.validation.accountLabelRequired': '取引先名を入力してください。',
  'deal.validation.amountPositive': '金額は0以上で入力してください。',
  'deal.validation.probabilityRange': '確度は0〜100で入力してください。',

  'locale.label': '言語',
}
