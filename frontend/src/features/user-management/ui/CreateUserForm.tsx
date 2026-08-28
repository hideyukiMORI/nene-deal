import { Button, FormField, Input, Select, Stack } from '@hideyukimori/nene2-ui'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { CreateUserInput, OperatorRole } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
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
      <Stack gap="2xs">
        <h2 className="t-h2">{t('users.create.title')}</h2>

        {errorMessage !== null ? <p className="t-cap danger">{errorMessage}</p> : null}

        <FormField
          id="user-email"
          label={t('users.field.email')}
          error={errors.email?.message ?? null}
        >
          <Input type="email" {...register('email')} />
        </FormField>
        <FormField
          id="user-password"
          label={t('users.field.password')}
          error={errors.password?.message ?? null}
        >
          <Input type="password" {...register('password')} />
        </FormField>
        <FormField
          id="user-role"
          label={t('users.field.role')}
          error={errors.role?.message ?? null}
        >
          <Select className="select-chevron" {...register('role')}>
            {roleOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </Select>
        </FormField>

        <Stack direction="horizontal" align="center" gap="2xs">
          <Button type="submit" disabled={pending}>
            {t('users.create.submit')}
          </Button>
          <Button type="button" variant="outline" tone="neutral" onClick={onCancel}>
            {t('common.actions.cancel')}
          </Button>
        </Stack>
      </Stack>
    </form>
  )
}
