import { Badge, EmptyState, FormField, Select } from '@hideyukimori/nene2-ui'
import type { SelectOption } from '@/shared/ui/select-option'
import { useState } from 'react'
import type { CreateUserInput, OperatorRole, OperatorUser, UserStatus } from '@/entities/user'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { IconChevron, IconClose, IconPlus } from '@/shared/ui/icons'
import type { UserManagementStatus } from '../model/use-user-management-page'
import { CreateUserForm } from './CreateUserForm'

export interface UserManagementViewProps {
  status: UserManagementStatus
  errorKey: MessageKey | null
  users: OperatorUser[]
  currentUserId: string | null
  retry: () => void
  createUser: (input: CreateUserInput) => Promise<boolean>
  createPending: boolean
  createErrorKey: MessageKey | null
  updateRole: (userId: string, role: OperatorRole) => Promise<boolean>
  updateStatus: (userId: string, status: UserStatus) => Promise<boolean>
  deleteUser: (userId: string) => Promise<boolean>
}

export function UserManagementView({
  status,
  errorKey,
  users,
  currentUserId,
  retry,
  createUser,
  createPending,
  createErrorKey,
  updateRole,
  updateStatus,
  deleteUser,
}: UserManagementViewProps) {
  const { t } = useTranslation()
  const [formOpen, setFormOpen] = useState(false)
  const [openUser, setOpenUser] = useState<OperatorUser | null>(null)

  const roleOptions: SelectOption[] = [
    { value: 'operator', label: t('users.role.operator') },
    { value: 'admin', label: t('users.role.admin') },
  ]

  const roleSelect = (user: OperatorUser, labelHidden: boolean) => {
    const select = (
      <Select
        id={`role-${user.id}`}
        value={user.role}
        disabled={user.id === currentUserId}
        {...(labelHidden ? { 'aria-label': t('users.field.role') } : {})}
        onChange={(e) => {
          void updateRole(user.id, e.target.value as OperatorRole)
        }}
      >
        {roleOptions.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </Select>
    )
    // labelHidden: キットの FormField に「ラベルを視覚的に隠す」入口が無い（ローカル
    // Select は sr-only を持っていた）。表の行では見出しが列側にあるので FormField を
    // 使わず aria-label で名前を与える＝意匠の特例ではなく支援技術向けの属性。
    return labelHidden ? (
      select
    ) : (
      <FormField id={`role-${user.id}`} label={t('users.field.role')}>
        {select}
      </FormField>
    )
  }

  const confirmDelete = (user: OperatorUser) => {
    if (window.confirm(t('users.delete.confirm'))) {
      void deleteUser(user.id)
      setOpenUser(null)
    }
  }

  const toggleStatus = (user: OperatorUser) => {
    if (user.status === 'active') {
      if (window.confirm(t('users.disable.confirm'))) {
        void updateStatus(user.id, 'disabled')
        setOpenUser(null)
      }
      return
    }
    void updateStatus(user.id, 'active')
    setOpenUser(null)
  }

  const statusButton = (user: OperatorUser) => (
    <button
      type="button"
      className="btn btn-secondary"
      onClick={() => {
        toggleStatus(user)
      }}
    >
      {user.status === 'active' ? t('users.disable.label') : t('users.enable.label')}
    </button>
  )

  return (
    <section className="content content-narrow flex flex-col">
      <div className="row justify-between flex-wrap g4 page-head">
        <div className="flex flex-col gap-1">
          <h1 className="t-h1">{t('users.title')}</h1>
          <span className="muted t-cap">{t('users.subtitle')}</span>
        </div>
        <button
          type="button"
          className="btn btn-primary"
          onClick={() => {
            setFormOpen((open) => !open)
          }}
        >
          <IconPlus />
          {t('users.create.open')}
        </button>
      </div>

      {formOpen ? (
        <CreateUserForm
          pending={createPending}
          errorMessage={createErrorKey !== null ? t(createErrorKey) : null}
          onSubmit={async (input) => {
            const ok = await createUser(input)
            if (ok) setFormOpen(false)
            return ok
          }}
          onCancel={() => {
            setFormOpen(false)
          }}
        />
      ) : null}

      {status === 'loading' ? <p className="muted text-body">{t('users.loading')}</p> : null}

      {status === 'error' ? (
        <div className="flex flex-col gap-3">
          <h2 className="t-h2">{t('users.error.title')}</h2>
          <p className="muted text-body">
            {errorKey !== null ? t(errorKey) : t('common.error.unknown')}
          </p>
          <div className="flex items-center gap-3">
            <button type="button" className="btn btn-secondary" onClick={retry}>
              {t('common.actions.retry')}
            </button>
          </div>
        </div>
      ) : null}

      {/* 🔴 EmptyState: description が落ちている（fleet-tooling#456 待ち）。キットは
                message 1本しか取らないので title だけを渡している＝意匠差ではなく**文言の欠落**。
                目視束の「キット既定に任せた箇所」に明記すること（hub 指示 2026-08-26）。 */}
      {status === 'ready' && users.length === 0 ? (
        <EmptyState message={t('users.empty.title')} />
      ) : null}

      {status === 'ready' && users.length > 0 ? (
        <div className="card">
          <div className="rows">
            {users.map((user) => {
              const you = user.id === currentUserId
              return (
                <div key={user.id} className="list-row user-row">
                  <button
                    type="button"
                    className="user-id"
                    aria-label={`${user.email} — ${t('users.detail.tap')}`}
                    onClick={() => {
                      setOpenUser(user)
                    }}
                  >
                    <span className="avatar cursor-pointer">
                      {(user.email[0] ?? '?').toUpperCase()}
                    </span>
                    <div className="stack gap-1 flex flex-col">
                      <span className="row gap-2 flex items-center">
                        <span className="medium">{user.email}</span>
                        {you ? <Badge tone="accent">{t('users.you')}</Badge> : null}
                        {user.status === 'disabled' ? (
                          <Badge tone="neutral">{t('users.status.disabled')}</Badge>
                        ) : null}
                      </span>
                      <span className="faint t-tiny mono">{user.id}</span>
                    </div>
                    <span className="row-chevron">
                      <IconChevron />
                    </span>
                  </button>
                  <div className="row gap-3 user-ctrls">
                    {roleSelect(user, true)}
                    {you ? null : statusButton(user)}
                    {you ? null : (
                      <button
                        type="button"
                        className="icon-btn"
                        title={t('users.delete.label')}
                        aria-label={t('users.delete.label')}
                        onClick={() => {
                          confirmDelete(user)
                        }}
                      >
                        ×
                      </button>
                    )}
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      ) : null}

      {/* Mobile user-detail sheet (CSS shows the wrapper ≤1024px). */}
      {openUser !== null ? (
        <div className="m-sheet-wrap open">
          <button
            type="button"
            className="m-sheet-scrim"
            aria-label={t('common.actions.cancel')}
            onClick={() => {
              setOpenUser(null)
            }}
          />
          <div className="m-sheet" role="dialog" aria-label={t('users.detail.title')}>
            <div className="bg-border-strong mx-auto mt-1 mb-1.5 h-1 w-10 rounded-full" />
            <div className="flex items-center justify-between">
              <span className="t-h2">{t('users.detail.title')}</span>
              <button
                type="button"
                className="icon-btn neutral"
                aria-label={t('common.actions.cancel')}
                onClick={() => {
                  setOpenUser(null)
                }}
              >
                <IconClose />
              </button>
            </div>
            <div className="us-id">
              <span
                className="avatar cursor-pointer"
                style={{ width: 48, height: 48, fontSize: 18 }}
              >
                {(openUser.email[0] ?? '?').toUpperCase()}
              </span>
              <div className="flex flex-col gap-1" style={{ minWidth: 0 }}>
                <span className="medium us-email">{openUser.email}</span>
                {openUser.id === currentUserId ? (
                  <Badge tone="accent" className="self-start">
                    {t('users.you')}
                  </Badge>
                ) : (
                  <span className="faint t-tiny">
                    {t('users.field.role')}:{' '}
                    {openUser.role === 'admin' ? t('users.role.admin') : t('users.role.operator')}
                  </span>
                )}
              </div>
            </div>
            <div className="field">
              <label>{t('users.detail.idLabel')}</label>
              <div className="us-code">{openUser.id}</div>
            </div>
            <div className="field">
              <label>{t('users.field.role')}</label>
              {roleSelect(openUser, true)}
            </div>
            {openUser.id === currentUserId ? null : statusButton(openUser)}
            {openUser.id === currentUserId ? null : (
              <button
                type="button"
                className="btn btn-danger us-del"
                onClick={() => {
                  confirmDelete(openUser)
                }}
              >
                {t('users.delete.label')}
              </button>
            )}
          </div>
        </div>
      ) : null}
    </section>
  )
}
