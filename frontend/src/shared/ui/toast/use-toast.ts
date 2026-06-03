import { useMemo } from 'react'
import { pushToast } from './toast-store'

export interface ToastApi {
  success: (title: string, sub?: string) => void
  error: (title: string, sub?: string) => void
}

/** Returns stable helpers to raise toasts. Safe to call without any provider. */
export function useToast(): ToastApi {
  return useMemo(
    () => ({
      success: (title: string, sub?: string) => {
        pushToast(title, sub, 'success')
      },
      error: (title: string, sub?: string) => {
        pushToast(title, sub, 'error')
      },
    }),
    [],
  )
}
