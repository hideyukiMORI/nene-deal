/**
 * i18n locales — Japanese and English only (ADR 0004). `ja` is the primary
 * default and the source-of-truth message catalog; `en` is a first-class peer
 * for foreign operators in Japan. No other locales without superseding the ADR.
 */

export type SupportedLocale = 'ja' | 'en'

export interface LocaleMeta {
  /** Native language name shown in the locale selector. */
  label: string
  /** Text direction. */
  dir: 'ltr' | 'rtl'
}

export const LOCALES: Record<SupportedLocale, LocaleMeta> = {
  ja: { label: '日本語', dir: 'ltr' },
  en: { label: 'English', dir: 'ltr' },
}

export const DEFAULT_LOCALE: SupportedLocale = 'ja'

export const SUPPORTED_LOCALE_IDS = Object.keys(LOCALES) as SupportedLocale[]

/**
 * Resolve a raw locale string (localStorage / navigator.language) to a
 * supported locale, falling back to the default. Examples: `en-US` → `en`,
 * `ja-JP` → `ja`, `fr` → `ja`.
 */
export function resolveLocale(raw: string): SupportedLocale {
  if (SUPPORTED_LOCALE_IDS.includes(raw as SupportedLocale)) {
    return raw as SupportedLocale
  }
  const prefix = raw.split('-')[0]
  if (prefix !== undefined && SUPPORTED_LOCALE_IDS.includes(prefix as SupportedLocale)) {
    return prefix as SupportedLocale
  }
  return DEFAULT_LOCALE
}
