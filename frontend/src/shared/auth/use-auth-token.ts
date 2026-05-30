import { useSyncExternalStore } from 'react'
import { authStore } from './auth-store'

/** Reactively reads the current bearer token (null when logged out). */
export function useAuthToken(): string | null {
  return useSyncExternalStore(
    (onChange) => authStore.subscribe(onChange),
    () => authStore.getToken(),
    () => authStore.getToken(),
  )
}
