import { useState } from 'react'
import type { CreateUserInput, OperatorRole, OperatorUser } from '@/entities/user'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Button, EmptyState, Select, Stack, Text, type SelectOption } from '@/shared/ui'
import type { UserManagementStatus } from '../hooks/use-user-management-page'
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
  deleteUser,
}: UserManagementViewProps) {
  const { t } = useTranslation()
  const [formOpen, setFormOpen] = useState(false)

  const roleOptions: SelectOption[] = [
    { value: 'operator', label: t('users.role.operator') },
    { value: 'admin', label: t('users.role.admin') },
  ]

  return (
    <main className="mx-auto flex max-w-3xl flex-col gap-stack-lg px-inline-lg py-stack-lg">
      <header className="flex flex-col gap-stack-sm">
        <Text as="h1" variant="heading-md">
          {t('users.title')}
        </Text>
        <Text muted>{t('users.subtitle')}</Text>
      </header>

      <Stack direction="horizontal" gap="sm">
        <Button
          onClick={() => {
            setFormOpen((open) => !open)
          }}
        >
          {t('users.create.open')}
        </Button>
      </Stack>

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

      {status === 'loading' ? <Text muted>{t('users.loading')}</Text> : null}

      {status === 'error' ? (
        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            {t('users.error.title')}
          </Text>
          <Text muted>{errorKey !== null ? t(errorKey) : t('common.error.unknown')}</Text>
          <Button variant="secondary" onClick={retry}>
            {t('common.actions.retry')}
          </Button>
        </Stack>
      ) : null}

      {status === 'ready' && users.length === 0 ? (
        <EmptyState title={t('users.empty.title')} description={t('users.empty.description')} />
      ) : null}

      {status === 'ready' && users.length > 0 ? (
        <ul className="flex flex-col gap-stack-sm">
          {users.map((user) => (
            <li
              key={user.id}
              className="flex flex-col gap-stack-xs rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
              <div className="flex flex-col gap-stack-xs">
                <Text as="span" className="font-medium">
                  {user.email}
                </Text>
                <Text as="span" variant="caption" muted>
                  {user.id}
                </Text>
              </div>

              <Stack direction="horizontal" gap="sm" className="items-center">
                <Select
                  id={`role-${user.id}`}
                  label={t('users.field.role')}
                  labelHidden
                  options={roleOptions}
                  value={user.role}
                  disabled={user.id === currentUserId}
                  onChange={(e) => {
                    void updateRole(user.id, e.target.value as OperatorRole)
                  }}
                />
                {user.id !== currentUserId ? (
                  <Button
                    variant="secondary"
                    size="sm"
                    onClick={() => {
                      if (window.confirm(t('users.delete.confirm'))) {
                        void deleteUser(user.id)
                      }
                    }}
                  >
                    {/* Delete icon — no label needed since it's adjacent to the user row */}×
                  </Button>
                ) : null}
              </Stack>
            </li>
          ))}
        </ul>
      ) : null}
    </main>
  )
}
