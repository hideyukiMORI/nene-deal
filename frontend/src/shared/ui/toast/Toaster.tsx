import { useCallback, useEffect, useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { IconCheck, IconClose } from '@/shared/ui/icons'
import { dismissToast, subscribeToasts, type Toast } from './toast-store'

const AUTO_DISMISS_MS = 2600
const LEAVE_MS = 220

/** App-root toast surface. Mount once inside the i18n provider. */
export function Toaster() {
  const { t } = useTranslation()
  const [toasts, setToasts] = useState<Toast[]>([])

  useEffect(() => subscribeToasts(setToasts), [])

  if (toasts.length === 0) {
    return null
  }

  return (
    <div className="toast-host" role="region" aria-live="polite" aria-label={t('toast.region')}>
      {toasts.map((toast) => (
        <ToastItem key={toast.id} toast={toast} dismissLabel={t('toast.dismiss')} />
      ))}
    </div>
  )
}

function ToastItem({ toast, dismissLabel }: { toast: Toast; dismissLabel: string }) {
  const [leaving, setLeaving] = useState(false)

  const close = useCallback(() => {
    setLeaving(true)
    setTimeout(() => {
      dismissToast(toast.id)
    }, LEAVE_MS)
  }, [toast.id])

  useEffect(() => {
    const timer = setTimeout(close, AUTO_DISMISS_MS)
    return () => {
      clearTimeout(timer)
    }
  }, [close])

  return (
    <button
      type="button"
      className={`toast toast-${toast.variant}${leaving ? ' leaving' : ''}`}
      aria-label={dismissLabel}
      onClick={close}
    >
      <span className="toast-ic">{toast.variant === 'error' ? <IconClose /> : <IconCheck />}</span>
      <span className="toast-body">
        <span className="toast-title">{toast.title}</span>
        {toast.sub !== undefined && toast.sub !== '' ? (
          <span className="toast-sub">{toast.sub}</span>
        ) : null}
      </span>
      <span className="toast-bar" />
    </button>
  )
}
