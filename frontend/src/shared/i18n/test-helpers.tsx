import { render, type RenderOptions, type RenderResult } from '@testing-library/react'
import type { ReactElement } from 'react'
import { I18nProvider } from './i18n-context'
import type { SupportedLocale } from './locales'

export interface RenderWithI18nOptions extends Omit<RenderOptions, 'wrapper'> {
  locale?: SupportedLocale
}

/**
 * Render a component wrapped in I18nProvider with the given locale.
 *
 * @example
 * const { getByText } = renderWithI18n(<MyComponent />, { locale: 'en' })
 */
export function renderWithI18n(
  ui: ReactElement,
  { locale = 'ja', ...options }: RenderWithI18nOptions = {},
): RenderResult {
  try {
    localStorage.setItem('nene-deal-locale', locale)
  } catch {
    // ignore
  }
  return render(ui, {
    wrapper: ({ children }) => <I18nProvider>{children}</I18nProvider>,
    ...options,
  })
}
