import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { CreateUserInput, OperatorRole } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import { Button } from '@/shared/ui/primitives/Button'
import { Input } from '@/shared/ui/primitives/Input'
import { Select } from '@/shared/ui/primitives/Select'
import { Stack } from '@/shared/ui/primitives/Stack'
import { Text } from '@/shared/ui/primitives/Text'
interface CreateUserFormProps {
  pending: boolean
  errorMessage: string | null
  onSubmit: (input: CreateUserInput) => Promise<boolean>
  onCancel: () => void
}

interface FormValues {
  email: string
  password: string
  role: OperatorRole
}

export function CreateUserForm({ pending, errorMessage, onSubmit, onCancel }: CreateUserFormProps) {
  const { t } = useTranslation()

  const schema = z.object({
    email: z.email(t('users.validation.emailRequired')).min(1, t('users.validation.emailRequired')),
    password: z.string().min(8, t('users.validation.passwordMin')),
    role: z.enum(['admin', 'operator'] as const, { error: t('users.validation.roleRequired') }),
  })

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '', role: 'operator' },
  })

  const submit = handleSubmit(async (values) => {
    const ok = await onSubmit(values)
    if (ok) reset()
  })

  const roleOptions = [
    { value: 'operator', label: t('users.role.operator') },
    { value: 'admin', label: t('users.role.admin') },
  ]

  return (
    <form
      noValidate
      onSubmit={(e) => {
        void submit(e)
      }}
      className="card card-pad"
    >
      <Stack gap="sm">
        <Text as="h2" variant="heading-sm">
          {t('users.create.title')}
        </Text>

        {errorMessage !== null ? (
          <Text variant="caption" className="danger">
            {errorMessage}
          </Text>
        ) : null}

        <Input
          id="user-email"
          label={t('users.field.email')}
          type="email"
          error={errors.email?.message}
          {...register('email')}
        />
        <Input
          id="user-password"
          label={t('users.field.password')}
          type="password"
          error={errors.password?.message}
          {...register('password')}
        />
        <Select
          id="user-role"
          label={t('users.field.role')}
          options={roleOptions}
          error={errors.role?.message}
          {...register('role')}
        />

        <Stack direction="horizontal" gap="sm">
          <Button type="submit" disabled={pending}>
            {t('users.create.submit')}
          </Button>
          <Button type="button" variant="secondary" onClick={onCancel}>
            {t('common.actions.cancel')}
          </Button>
        </Stack>
      </Stack>
    </form>
  )
}
