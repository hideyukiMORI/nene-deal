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

/** Diamond brand glyph, inherits `currentColor` so it adapts to its panel. */
function BrandMark({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 24 24" aria-hidden className={className} fill="none">
      <rect x="1" y="1" width="22" height="22" rx="6" stroke="currentColor" strokeWidth="1.5" />
      <path d="M12 6l6 6-6 6-6-6 6-6z" fill="currentColor" />
    </svg>
  )
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
    <main className="grid min-h-screen lg:grid-cols-2">
      {/* Brand panel — accent gradient, hidden below the lg breakpoint. */}
      <aside className="hidden flex-col justify-between bg-gradient-to-br from-accent to-accent-hover px-inline-xl py-stack-xl text-text-inverse lg:flex">
        <div className="flex items-center gap-inline-sm">
          <BrandMark className="size-7" />
          <Text as="span" variant="heading-sm" className="text-text-inverse">
            {t('app.title')}
          </Text>
        </div>

        <Stack gap="sm">
          <Text as="p" variant="heading-md" className="max-w-md text-text-inverse">
            {t('app.subtitle')}
          </Text>
          <Text as="p" className="max-w-sm text-text-inverse opacity-80">
            {t('login.brandTagline')}
          </Text>
        </Stack>

        <Text variant="caption" className="text-text-inverse opacity-70">
          © NeNe Deal
        </Text>
      </aside>

      {/* Form panel. */}
      <div className="flex items-center justify-center bg-surface px-inline-lg py-stack-xl">
        <form
          noValidate
          onSubmit={(event) => {
            void submit(event)
          }}
          className="w-full max-w-sm rounded-md border border-border bg-surface-raised px-inline-lg py-stack-lg shadow-md"
        >
          <Stack gap="md">
            {/* Compact brand lockup shown only when the brand panel is hidden. */}
            <div className="flex items-center gap-inline-sm text-accent lg:hidden">
              <BrandMark className="size-6" />
              <Text as="span" variant="heading-sm" className="text-text-primary">
                {t('app.title')}
              </Text>
            </div>

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
              type="email"
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

            <Button type="submit" className="w-full" disabled={pending}>
              {t('login.submit')}
            </Button>
          </Stack>
        </form>
      </div>
    </main>
  )
}
