import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'

export function NotFoundPage() {
  const { t } = useTranslation()

  return (
    <div className="min-h-screen">
      <section className="notfound gap-4" style={{ alignContent: 'center' }}>
        <span className="code">404</span>
        <h1 className="t-h1">{t('common.error.notFound')}</h1>
        <p className="muted text-body" style={{ maxWidth: '44ch' }}>
          {t('common.error.unknown')}
        </p>
        <div className="flex items-center gap-3" style={{ justifyContent: 'center', marginTop: 8 }}>
          <Link className="btn btn-primary" to="/">
            {t('detail.back')}
          </Link>
        </div>
      </section>
    </div>
  )
}
