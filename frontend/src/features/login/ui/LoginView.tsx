import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { LoginInput } from '@/entities/auth'
import { LOCALES, SUPPORTED_LOCALE_IDS, useTranslation, type SupportedLocale } from '@/shared/i18n'
import { useTheme } from '@/shared/theme'
import { LangToggle } from '@/shared/ui/components/LangToggle'
import { ThemeToggle } from '@/shared/ui/components/ThemeToggle'
import { IconLogo, IconShield } from '@/shared/ui/icons'

export interface LoginViewProps {
  pending: boolean
  errorMessage: string | null
  onSubmit: (input: LoginInput) => Promise<boolean>
}

interface LoginFormValues {
  email: string
  password: string
}

export function LoginView({ pending, errorMessage, onSubmit }: LoginViewProps) {
  const { t, locale, setLocale } = useTranslation()
  const { theme, setTheme } = useTheme()

  const localeItems = SUPPORTED_LOCALE_IDS.map((id) => ({
    id,
    label: id === 'en' ? 'EN' : LOCALES[id].label,
  }))

  const schema = z.object({
    email: z.string().trim().min(1, t('login.validation.emailRequired')),
    password: z.string().min(1, t('login.validation.passwordRequired')),
  })

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '' },
  })

  const submit = handleSubmit(async (values) => {
    await onSubmit(values)
  })

  return (
    <div className="fs-wrap">
      <div className="fs-lang">
        <span className="hdr-controls">
          <ThemeToggle
            theme={theme}
            onThemeChange={setTheme}
            groupLabel={t('shell.theme')}
            lightLabel={t('shell.themeLight')}
            darkLabel={t('shell.themeDark')}
          />
          <LangToggle
            items={localeItems}
            activeId={locale}
            onSelect={(id) => {
              setLocale(id as SupportedLocale)
            }}
            groupLabel={t('shell.lang')}
          />
        </span>
      </div>

      <div className="auth" style={{ minHeight: '100vh' }}>
        <aside className="auth-brand">
          <div className="grid-deco" />
          <div className="row g3">
            <span className="brand-logo">
              <IconLogo />
            </span>
            <span className="semi" style={{ fontSize: 16 }}>
              NeNe&nbsp;Deal
            </span>
          </div>
          <div className="stack g4" style={{ maxWidth: '32ch' }}>
            <span className="eyebrow">{t('login.heroEyebrow')}</span>
            <h2 className="t-display">{t('login.heroTitle')}</h2>
            <p className="muted t-body">{t('login.heroBody')}</p>
            <div className="row wrap g2" style={{ marginTop: 8 }}>
              <span className="kpi-chip">
                {t('login.kpiActive')} <span className="v">12</span>
              </span>
              <span className="kpi-chip">
                {t('login.kpiWeighted')} <span className="v">￥3.15M</span>
              </span>
              <span className="kpi-chip">
                {t('login.kpiUptime')} <span className="v">99.9%</span>
              </span>
            </div>
          </div>
          <span className="faint t-tiny">© 2026 NeNe Deal</span>
        </aside>

        <div className="auth-form">
          <form
            noValidate
            onSubmit={(event) => {
              void submit(event)
            }}
            className="auth-card stack g6"
          >
            <div className="stack g2">
              <h1 className="t-h1">{t('login.title')}</h1>
              <span className="muted t-cap">{t('login.subtitle')}</span>
            </div>

            {errorMessage !== null ? <span className="t-cap danger">{errorMessage}</span> : null}

            <div className="field">
              <label htmlFor="login-email">{t('login.email')}</label>
              <input
                id="login-email"
                className="input"
                type="email"
                placeholder="operator@nene-deal.test"
                {...register('email')}
              />
              {errors.email?.message !== undefined ? (
                <span className="t-tiny danger">{errors.email.message}</span>
              ) : null}
            </div>

            <div className="field">
              <label htmlFor="login-password">{t('login.password')}</label>
              <input
                id="login-password"
                className="input"
                type="password"
                placeholder="••••••••"
                {...register('password')}
              />
              {errors.password?.message !== undefined ? (
                <span className="t-tiny danger">{errors.password.message}</span>
              ) : null}
            </div>

            <button className="btn btn-primary btn-block" type="submit" disabled={pending}>
              {t('login.submit')}
            </button>

            <div className="row g2 faint t-tiny" style={{ justifyContent: 'center' }}>
              <IconShield />
              <span>{t('login.secure')}</span>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
