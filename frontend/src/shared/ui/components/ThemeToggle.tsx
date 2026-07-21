import { IconMoon, IconSun } from '@/shared/ui/icons'

export interface ThemeToggleProps {
  /** Currently active color mode. */
  theme: 'light' | 'dark'
  /** Called with the mode to activate. */
  onThemeChange: (mode: 'light' | 'dark') => void
  /** Accessible group title (e.g. t('shell.theme')). */
  groupLabel: string
  /** Light-mode button label (e.g. t('shell.themeLight')). */
  lightLabel: string
  /** Dark-mode button label (e.g. t('shell.themeDark')). */
  darkLabel: string
}

/**
 * Sun / moon color-mode toggle (presentation-only). State and labels are
 * supplied by the consumer, which owns the theme hook and i18n (R1②: shared/ui
 * does not import i18n or app state).
 */
export function ThemeToggle({
  theme,
  onThemeChange,
  groupLabel,
  lightLabel,
  darkLabel,
}: ThemeToggleProps) {
  return (
    <span className="mode-toggle" title={groupLabel}>
      <button
        type="button"
        className={theme === 'light' ? 'active' : ''}
        title={lightLabel}
        aria-label={lightLabel}
        aria-pressed={theme === 'light'}
        onClick={() => {
          onThemeChange('light')
        }}
      >
        <IconSun />
      </button>
      <button
        type="button"
        className={theme === 'dark' ? 'active' : ''}
        title={darkLabel}
        aria-label={darkLabel}
        aria-pressed={theme === 'dark'}
        onClick={() => {
          onThemeChange('dark')
        }}
      >
        <IconMoon />
      </button>
    </span>
  )
}
