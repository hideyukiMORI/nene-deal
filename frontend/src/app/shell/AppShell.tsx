import { useState, type ComponentType, type SVGProps } from 'react'
import { Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useCurrentUser } from '@/entities/auth'
import {
  LOCALES,
  SUPPORTED_LOCALE_IDS,
  useTranslation,
  type MessageKey,
  type SupportedLocale,
} from '@/shared/i18n'
import { useTheme } from '@/shared/theme'
import { LangToggle } from '@/shared/ui/components/LangToggle'
import { ThemeToggle } from '@/shared/ui/components/ThemeToggle'
import {
  IconAccount,
  IconBack,
  IconBoard,
  IconChevron,
  IconClose,
  IconLogo,
  IconSettings,
  IconShield,
  IconStages,
  IconUsers,
} from '@/shared/ui/icons'
import { SignoutButton } from './Toggles'

type ScreenKey = 'board' | 'detail' | 'users' | 'stages' | 'audit' | 'settings'

interface NavEntry {
  key: Exclude<ScreenKey, 'detail'>
  to: string
  label: MessageKey
  Icon: ComponentType<SVGProps<SVGSVGElement>>
  admin?: boolean
}

const NAV: NavEntry[] = [
  { key: 'board', to: '/', label: 'nav.board', Icon: IconBoard },
  { key: 'stages', to: '/stages', label: 'nav.stages', Icon: IconStages, admin: true },
  { key: 'users', to: '/users', label: 'nav.users', Icon: IconUsers, admin: true },
  { key: 'audit', to: '/audit', label: 'nav.audit', Icon: IconShield, admin: true },
  { key: 'settings', to: '/settings', label: 'nav.settings', Icon: IconSettings, admin: true },
]

function screenFromPath(pathname: string): ScreenKey {
  if (pathname.startsWith('/deals')) return 'detail'
  if (pathname.startsWith('/users')) return 'users'
  if (pathname.startsWith('/stages')) return 'stages'
  if (pathname.startsWith('/audit')) return 'audit'
  if (pathname.startsWith('/settings')) return 'settings'
  return 'board'
}

function isActive(key: NavEntry['key'], screen: ScreenKey): boolean {
  return key === screen || (key === 'board' && screen === 'detail')
}

function Brand() {
  const { t } = useTranslation()
  return (
    <div className="brandmark">
      <span className="logo">
        <IconLogo />
      </span>
      <div className="stack" style={{ lineHeight: 1.12 }}>
        <span className="name">NeNe Deal</span>
        <span className="sub">{t('app.subtitle')}</span>
      </div>
    </div>
  )
}

/**
 * Calm app shell: desktop top nav + a completely separate mobile UI
 * (sticky top bar, bottom tab bar, and an account/settings bottom sheet),
 * switched purely by CSS at the ≤1024px breakpoint. Wraps authenticated routes
 * via <Outlet/>. Each screen keeps its own header "Add" button (visible on
 * mobile too), so creation works on every screen without a FAB.
 */
export function AppShell() {
  const { t, locale, setLocale } = useTranslation()
  const { theme, setTheme } = useTheme()
  const navigate = useNavigate()
  const location = useLocation()
  const currentUser = useCurrentUser()
  const [sheetOpen, setSheetOpen] = useState(false)

  const localeItems = SUPPORTED_LOCALE_IDS.map((id) => ({
    id,
    label: id === 'en' ? 'EN' : LOCALES[id].label,
  }))

  const screen = screenFromPath(location.pathname)
  const isAdmin = currentUser.data?.role === 'admin'
  const email = currentUser.data?.email ?? 'operator@nene-deal.test'
  const avatarLetter = (email[0] ?? 'O').toUpperCase()
  const navItems = NAV.filter((n) => n.admin !== true || isAdmin)
  // Settings is reachable from the desktop nav and the mobile account sheet,
  // but is kept out of the bottom tab bar (design spec).
  const mobileTabs = navItems.filter((n) => n.key !== 'settings')

  const go = (to: string) => {
    setSheetOpen(false)
    void navigate(to)
  }

  const accountBlock = (
    <div className="border-border flex items-center gap-3 border-b pb-4">
      <span className="avatar cursor-pointer" style={{ width: 40, height: 40 }}>
        {avatarLetter}
      </span>
      <div className="stack gap-1" style={{ flex: 1, minWidth: 0 }}>
        <span className="medium" style={{ overflow: 'hidden', textOverflow: 'ellipsis' }}>
          {email}
        </span>
        <span className="faint t-tiny row gap-1">
          <IconShield />
          {t('users.role.admin')}
        </span>
      </div>
    </div>
  )

  return (
    <div className="shell shell-calm">
      {/* Desktop top nav */}
      <header className="topnav">
        <div className="row gap-3">
          <Brand />
        </div>
        <nav className="flex gap-0.5">
          {navItems.map((n) => (
            <button
              key={n.key}
              type="button"
              className={`topnav-link ${isActive(n.key, screen) ? 'active' : ''}`}
              onClick={() => {
                go(n.to)
              }}
            >
              {t(n.label)}
            </button>
          ))}
        </nav>
        <div className="flex items-center gap-3">
          <span className="inline-flex items-center gap-2">
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
          <span className="nav-divider" />
          <span className="inline-flex items-center">
            <span className="avatar cursor-pointer">{avatarLetter}</span>
          </span>
          <SignoutButton />
        </div>
      </header>

      {/* Mobile top bar */}
      <header className="m-topbar">
        {screen === 'detail' || screen === 'settings' ? (
          <button
            type="button"
            className="m-back"
            aria-label={t('detail.back')}
            onClick={() => {
              go('/')
            }}
          >
            <IconBack />
          </button>
        ) : (
          <span className="m-brand">
            <span className="logo">
              <IconLogo />
            </span>
            <span className="semi">NeNe&nbsp;Deal</span>
          </span>
        )}
        <button
          type="button"
          className="grid size-10 cursor-pointer place-items-center border-none bg-none p-0"
          aria-label={t('shell.account')}
          onClick={() => {
            setSheetOpen(true)
          }}
        >
          <span className="avatar cursor-pointer">{avatarLetter}</span>
        </button>
      </header>

      <div className="main">
        <div className="main-inner">
          <Outlet />
        </div>
      </div>

      {/* Mobile bottom tabs — primary nav (no Settings) + the account tab;
          columns are dynamic so the account tab never wraps (design spec). */}
      <nav
        className="m-tabs"
        style={{ gridTemplateColumns: `repeat(${String(mobileTabs.length + 1)}, 1fr)` }}
      >
        {mobileTabs.map((n) => {
          const Icon = n.Icon
          return (
            <button
              key={n.key}
              type="button"
              className={`m-tab ${isActive(n.key, screen) ? 'active' : ''}`}
              onClick={() => {
                go(n.to)
              }}
            >
              <Icon className="ico" />
              <span>{t(n.label)}</span>
            </button>
          )
        })}
        <button
          type="button"
          className={`m-tab ${screen === 'settings' ? 'active' : ''}`}
          onClick={() => {
            setSheetOpen(true)
          }}
        >
          <IconAccount className="ico" />
          <span>{t('shell.account')}</span>
        </button>
      </nav>

      {/* Mobile account / settings sheet */}
      <div className={`m-sheet-wrap${sheetOpen ? ' open' : ''}`}>
        <button
          type="button"
          className="m-sheet-scrim"
          aria-label={t('common.actions.cancel')}
          onClick={() => {
            setSheetOpen(false)
          }}
        />
        <div className="m-sheet" role="dialog" aria-label={t('shell.settingsTitle')}>
          <div className="bg-border-strong mx-auto mt-1 mb-1.5 h-1 w-10 rounded-full" />
          <div className="flex items-center justify-between">
            <span className="t-h2">{t('shell.settingsTitle')}</span>
            <button
              type="button"
              className="icon-btn neutral"
              aria-label={t('common.actions.cancel')}
              onClick={() => {
                setSheetOpen(false)
              }}
            >
              <IconClose />
            </button>
          </div>
          {accountBlock}
          {isAdmin ? (
            <button
              type="button"
              className="m-sheet-link"
              onClick={() => {
                go('/settings')
              }}
            >
              <IconSettings />
              <span>{t('nav.settings')}</span>
              <IconChevron />
            </button>
          ) : null}
          <div className="flex items-center justify-between gap-3">
            <span className="text-text-muted text-body font-semibold">{t('shell.theme')}</span>
            <ThemeToggle
              theme={theme}
              onThemeChange={setTheme}
              groupLabel={t('shell.theme')}
              lightLabel={t('shell.themeLight')}
              darkLabel={t('shell.themeDark')}
            />
          </div>
          <div className="flex items-center justify-between gap-3">
            <span className="text-text-muted text-body font-semibold">{t('shell.lang')}</span>
            <LangToggle
              items={localeItems}
              activeId={locale}
              onSelect={(id) => {
                setLocale(id as SupportedLocale)
              }}
              groupLabel={t('shell.lang')}
            />
          </div>
          <SignoutButton />
        </div>
      </div>
    </div>
  )
}
