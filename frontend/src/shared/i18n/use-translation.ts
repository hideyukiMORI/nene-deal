import { useContext } from 'react'
import { I18nContext, type I18nContextValue } from './i18n-context-ref'

/**
 * Primary i18n hook.
 *
 * @example
 * const { t, locale, setLocale } = useTranslation()
 * return <h1>{t('app.title')}</h1>
 */
export function useTranslation(): I18nContextValue {
  const ctx = useContext(I18nContext)
  if (ctx === null) {
    throw new Error('useTranslation must be called inside <I18nProvider>')
  }
  return ctx
}
