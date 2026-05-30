import { useNavigate } from 'react-router-dom'
import { LoginView, useLoginPage } from '@/features/login'
import { useTranslation } from '@/shared/i18n'

export function LoginPage() {
  const page = useLoginPage()
  const navigate = useNavigate()
  const { t } = useTranslation()

  return (
    <LoginView
      pending={page.pending}
      errorMessage={page.errorKey !== null ? t('login.failed') : null}
      onSubmit={async (input) => {
        const ok = await page.submit(input)
        if (ok) {
          void navigate('/')
        }
        return ok
      }}
    />
  )
}
