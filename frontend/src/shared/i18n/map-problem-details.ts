import type { AppError } from '@/shared/api/client'
import type { MessageKey } from './translate'

/**
 * Map an {@link AppError} (HTTP Problem Details) to a localizable message key
 * for user-facing display. API error titles themselves stay in English.
 */
export function mapProblemDetailsToMessageKey(error: AppError): MessageKey {
  switch (error.status) {
    case 401:
      return 'common.error.unauthorized'
    case 403:
      return 'common.error.forbidden'
    case 404:
      return 'common.error.notFound'
    case 409:
      return 'common.error.conflict'
    case 422:
      return 'common.error.validation'
    case 429:
      return 'common.error.rateLimit'
    default:
      return error.status >= 500 ? 'common.error.serverError' : 'common.error.unknown'
  }
}
