import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import type { LoginInput } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'

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
  const { t } = useTranslation()

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
    <main className="mx-auto flex min-h-screen max-w-md items-center px-inline-lg py-stack-xl">
      <form
        noValidate
        onSubmit={(event) => {
          void submit(event)
        }}
        className="w-full rounded-md border border-border bg-surface-raised px-inline-lg py-stack-lg shadow-sm"
      >
        <Stack gap="md">
          <Stack gap="xs">
            <Text as="h1" variant="heading-md">
              {t('login.title')}
            </Text>
            <Text muted>{t('login.subtitle')}</Text>
          </Stack>

          {errorMessage !== null ? (
            <Text variant="caption" className="text-danger">
              {errorMessage}
            </Text>
          ) : null}

          <Input
            id="login-email"
            label={t('login.email')}
            error={errors.email?.message}
            {...register('email')}
          />
          <Input
            id="login-password"
            label={t('login.password')}
            type="password"
            error={errors.password?.message}
            {...register('password')}
          />

          <Button type="submit" disabled={pending}>
            {t('login.submit')}
          </Button>
        </Stack>
      </form>
    </main>
  )
}
