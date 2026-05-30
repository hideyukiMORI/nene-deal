import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function NotFoundPage() {
  const { t } = useTranslation()

  return (
    <main className="mx-auto flex min-h-screen max-w-3xl items-center px-inline-lg py-stack-xl">
      <Stack gap="sm">
        <Text as="h1" variant="heading-md">
          404
        </Text>
        <Text muted>{t('common.error.notFound')}</Text>
        <Link to="/" className="font-sans text-body text-accent hover:text-accent-hover">
          {t('app.title')}
        </Link>
      </Stack>
    </main>
  )
}
