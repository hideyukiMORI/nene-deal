import { useNavigate } from 'react-router-dom'
import { authStore } from '@/shared/auth'
import { LOCALES, SUPPORTED_LOCALE_IDS, useTranslation } from '@/shared/i18n'
import { useTheme } from '@/shared/theme'
import { IconArrowOut, IconGlobe, IconMoon, IconSun } from '@/shared/ui/icons'

/** Sun / moon color-mode toggle (light default + dark). */
export function ThemeToggle() {
  const { t } = useTranslation()
  const { theme, setTheme } = useTheme()
  return (
    <span className="mode-toggle" title={t('shell.theme')}>
      <button
        type="button"
        className={theme === 'light' ? 'active' : ''}
        title={t('shell.themeLight')}
        aria-label={t('shell.themeLight')}
        aria-pressed={theme === 'light'}
        onClick={() => {
          setTheme('light')
        }}
      >
        <IconSun />
      </button>
      <button
        type="button"
        className={theme === 'dark' ? 'active' : ''}
        title={t('shell.themeDark')}
        aria-label={t('shell.themeDark')}
        aria-pressed={theme === 'dark'}
        onClick={() => {
          setTheme('dark')
        }}
      >
        <IconMoon />
      </button>
    </span>
  )
}

/** Globe + 日本語 / EN language toggle. */
export function LangToggle() {
  const { t, locale, setLocale } = useTranslation()
  return (
    <span className="lang-toggle" title={t('shell.lang')}>
      <IconGlobe />
      {SUPPORTED_LOCALE_IDS.map((id) => (
        <button
          key={id}
          type="button"
          className={locale === id ? 'active' : ''}
          aria-pressed={locale === id}
          onClick={() => {
            setLocale(id)
          }}
        >
          {id === 'en' ? 'EN' : LOCALES[id].label}
        </button>
      ))}
    </span>
  )
}

/** Sign out — clears the bearer token and returns to the login screen. */
export function SignoutButton() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  return (
    <button
      type="button"
      className="signout-btn"
      onClick={() => {
        authStore.clear()
        void navigate('/login')
      }}
    >
      <IconArrowOut />
      <span>{t('shell.signout')}</span>
    </button>
  )
}
