import type { MessageCatalog } from './ja'

/** English catalog — a first-class peer of the Japanese source of truth. */
export const en: MessageCatalog = {
  'app.title': 'NeNe Deal',
  'app.subtitle': 'Sales pipeline',

  'common.actions.create': 'Create',
  'common.actions.cancel': 'Cancel',
  'common.actions.retry': 'Retry',

  'common.error.unauthorized': 'Authentication is required.',
  'common.error.forbidden': 'You do not have permission for this action.',
  'common.error.notFound': 'The requested item was not found.',
  'common.error.conflict': 'A conflict occurred.',
  'common.error.validation': 'Please check your input.',
  'common.error.rateLimit': 'Too many requests. Please wait a moment.',
  'common.error.serverError': 'A server error occurred.',
  'common.error.unknown': 'An unexpected error occurred.',

  'board.loading': 'Loading the board…',
  'board.error.title': 'Could not load the board',
  'board.empty.title': 'No stages yet',
  'board.empty.description': 'No pipeline stages are configured.',
  'board.column.summary': '{{count}} deals · weighted {{weighted}}',
  'board.column.empty': 'No deals',

  'forecast.title': 'This month’s forecast',
  'forecast.openDealCount': 'Open deals',
  'forecast.pipelineTotal': 'Pipeline total',
  'forecast.weightedTotal': 'Weighted forecast',

  'deal.create.open': 'Add deal',
  'deal.create.title': 'New deal',
  'deal.create.submit': 'Create',
  'deal.create.error': 'Could not create the deal.',
  'deal.field.accountLabel': 'Account',
  'deal.field.amount': 'Amount (JPY)',
  'deal.field.stage': 'Stage',
  'deal.field.probability': 'Probability (%)',
  'deal.validation.accountLabelRequired': 'Please enter an account name.',
  'deal.validation.amountPositive': 'Amount must be 0 or greater.',
  'deal.validation.probabilityRange': 'Probability must be between 0 and 100.',

  'locale.label': 'Language',
}
