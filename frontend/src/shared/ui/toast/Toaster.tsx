import { useCallback, useEffect, useState } from 'react'
import { IconCheck, IconClose } from '@/shared/ui/icons'
import { dismissToast, subscribeToasts, type Toast } from './toast-store'

const AUTO_DISMISS_MS = 2600
const LEAVE_MS = 220

export interface ToasterProps {
  /** Accessible label for the live region (e.g. t('toast.region')). */
  regionLabel: string
  /** Accessible label for each toast's dismiss action (e.g. t('toast.dismiss')). */
  dismissLabel: string
}

/**
 * App-root toast surface (presentation-only). Mount once via an app-layer
 * adapter that supplies the labels from i18n (R1②: shared/ui does not import
 * i18n).
 */
export function Toaster({ regionLabel, dismissLabel }: ToasterProps) {
  const [toasts, setToasts] = useState<Toast[]>([])

  useEffect(() => subscribeToasts(setToasts), [])

  if (toasts.length === 0) {
    return null
  }

  return (
    <div className="toast-host" role="region" aria-live="polite" aria-label={regionLabel}>
      {toasts.map((toast) => (
        <ToastItem key={toast.id} toast={toast} dismissLabel={dismissLabel} />
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
