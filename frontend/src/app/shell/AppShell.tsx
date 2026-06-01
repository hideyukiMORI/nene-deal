import { useState, type ComponentType, type SVGProps } from 'react'
import { Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useCurrentUser } from '@/entities/auth'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import {
  IconAccount,
  IconBack,
  IconBoard,
  IconClose,
  IconLogo,
  IconShield,
  IconStages,
  IconUsers,
} from '@/shared/ui/icons'
import { LangToggle, SignoutButton, ThemeToggle } from './Toggles'

type ScreenKey = 'board' | 'detail' | 'users' | 'stages'

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
]

function screenFromPath(pathname: string): ScreenKey {
  if (pathname.startsWith('/deals')) return 'detail'
  if (pathname.startsWith('/users')) return 'users'
  if (pathname.startsWith('/stages')) return 'stages'
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
  const { t } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const currentUser = useCurrentUser()
  const [sheetOpen, setSheetOpen] = useState(false)

  const screen = screenFromPath(location.pathname)
  const isAdmin = currentUser.data?.role === 'admin'
  const email = currentUser.data?.email ?? 'operator@nene-deal.test'
  const avatarLetter = (email[0] ?? 'O').toUpperCase()
  const navItems = NAV.filter((n) => n.admin !== true || isAdmin)

  const go = (to: string) => {
    setSheetOpen(false)
    void navigate(to)
  }

  const accountBlock = (
    <div className="m-account">
      <span className="avatar" style={{ width: 40, height: 40 }}>
        {avatarLetter}
      </span>
      <div className="stack g1" style={{ flex: 1, minWidth: 0 }}>
        <span className="medium" style={{ overflow: 'hidden', textOverflow: 'ellipsis' }}>
          {email}
        </span>
        <span className="faint t-tiny row g1">
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
        <div className="row g3">
          <Brand />
        </div>
        <nav className="topnav-links">
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
        <div className="topnav-right">
          <span className="hdr-controls">
            <ThemeToggle />
            <LangToggle />
          </span>
          <span className="nav-divider" />
          <span className="account-chip">
            <span className="avatar">{avatarLetter}</span>
          </span>
          <SignoutButton />
        </div>
      </header>

      {/* Mobile top bar */}
      <header className="m-topbar">
        {screen === 'detail' ? (
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
          className="m-acct"
          aria-label={t('shell.account')}
          onClick={() => {
            setSheetOpen(true)
          }}
        >
          <span className="avatar">{avatarLetter}</span>
        </button>
      </header>

      <div className="main">
        <div className="main-inner">
          <Outlet />
        </div>
      </div>

      {/* Mobile bottom tabs */}
      <nav className="m-tabs">
        {navItems.map((n) => {
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
          className="m-tab"
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
          <div className="m-sheet-grab" />
          <div className="m-sheet-head">
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
          <div className="m-sheet-row">
            <span className="mm-label">{t('shell.theme')}</span>
            <ThemeToggle />
          </div>
          <div className="m-sheet-row">
            <span className="mm-label">{t('shell.lang')}</span>
            <LangToggle />
          </div>
          <SignoutButton />
        </div>
      </div>
    </div>
  )
}
