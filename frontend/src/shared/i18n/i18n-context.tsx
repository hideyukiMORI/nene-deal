import { useCallback, useEffect, useState, type ReactNode } from 'react'
import { LOCALES, resolveLocale, type SupportedLocale } from './locales'
import { applyLocaleFontFamily } from './locale-fonts'
import { getMessages } from './messages'
import { translate, type MessageKey, type MessageParams } from './translate'
import { I18nContext } from './i18n-context-ref'

const STORAGE_KEY = 'nene-deal-locale'

function detectLocale(): SupportedLocale {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored !== null) return resolveLocale(stored)
  } catch {
    // localStorage blocked (e.g. private mode)
  }
  return resolveLocale(navigator.language)
}

function applyLocaleToDocument(locale: SupportedLocale): void {
  document.documentElement.lang = locale
  document.documentElement.dir = LOCALES[locale].dir
  applyLocaleFontFamily(locale)
}

export function I18nProvider({ children }: { children: ReactNode }) {
  const [locale, setLocaleState] = useState<SupportedLocale>(detectLocale)

  const setLocale = useCallback((next: SupportedLocale) => {
    try {
      localStorage.setItem(STORAGE_KEY, next)
    } catch {
      // ignore storage errors
    }
    setLocaleState(next)
    applyLocaleToDocument(next)
  }, [])

  useEffect(() => {
    applyLocaleToDocument(locale)
  }, [locale])

  const messages = getMessages(locale)

  const t = useCallback(
    (key: MessageKey, params?: MessageParams): string => translate(messages, key, params),
    [messages],
  )

  return <I18nContext.Provider value={{ locale, setLocale, t }}>{children}</I18nContext.Provider>
}
