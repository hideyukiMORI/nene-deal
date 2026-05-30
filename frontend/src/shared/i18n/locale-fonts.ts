import type { SupportedLocale } from './locales'

/**
 * CSS custom property Tailwind v4 reads for `font-sans`. Setting it inline on
 * the document element overrides the `@theme` declaration so the active locale
 * gets the right script font.
 */
export const ADMIN_FONT_FAMILY_VAR = '--font-sans'

const LOCALE_FONT_STACKS: Record<SupportedLocale, string> = {
  ja: '"Noto Sans JP", "Hiragino Sans", "Yu Gothic UI", sans-serif',
  en: '"Inter", ui-sans-serif, system-ui, sans-serif',
}

export function getLocaleFontStack(locale: SupportedLocale): string {
  return LOCALE_FONT_STACKS[locale]
}

export function applyLocaleFontFamily(
  locale: SupportedLocale,
  root: HTMLElement = document.documentElement,
): void {
  root.style.setProperty(ADMIN_FONT_FAMILY_VAR, getLocaleFontStack(locale))
}
