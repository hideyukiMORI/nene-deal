import { useNavigate } from 'react-router-dom'
import { authStore } from '@/shared/auth'
import { useTranslation } from '@/shared/i18n'
import { IconArrowOut } from '@/shared/ui/icons'

/** Sign out — clears the bearer token and returns to the login screen. */
export function SignoutButton() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  return (
    <button
      type="button"
      className="signout-btn"
      onClick={() => {
        authStore.clear()
        void navigate('/login')
      }}
    >
      <IconArrowOut />
      <span>{t('shell.signout')}</span>
    </button>
  )
}
