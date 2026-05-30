import { createContext } from 'react'
import type { SupportedLocale } from './locales'
import type { MessageKey, MessageParams } from './translate'

/**
 * i18n context value + context object. Kept in a `.ts` file (no JSX) so the
 * `.tsx` provider only exports a component (Vite fast-refresh compatibility).
 */
export interface I18nContextValue {
  locale: SupportedLocale
  setLocale: (locale: SupportedLocale) => void
  t: (key: MessageKey, params?: MessageParams) => string
}

export const I18nContext = createContext<I18nContextValue | null>(null)
