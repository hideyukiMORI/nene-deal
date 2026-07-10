import { authStore } from './auth-store'

/**
 * One-shot handoff key the backend demo seat page writes
 * (`src/Demo/DemoSessionSeater.php` — keep the two in sync). The disposable
 * demo (`GET /demo/{template}`, #69) provisions a throwaway org, mints a
 * normal bearer token for its admin and parks it here before redirecting
 * into the SPA.
 */
const SEAT_STORAGE_KEY = 'nene-deal-demo-seat'

/**
 * Imports a parked demo seat token into the in-memory auth store, then
 * removes the parked copy immediately so the token never outlives the boot
 * that consumed it (the store is deliberately memory-only — reloading ends
 * the session, and a demo visitor simply revisits the demo URL for a fresh
 * org). No-op when no seat token is parked or sessionStorage is unavailable.
 *
 * Must run before the first render so the auth gate sees the session.
 */
export function importDemoSeat(): void {
  let token: string | null
  try {
    token = sessionStorage.getItem(SEAT_STORAGE_KEY)
    if (token !== null) {
      sessionStorage.removeItem(SEAT_STORAGE_KEY)
    }
  } catch {
    return // sessionStorage unavailable (e.g. blocked storage): stay logged out
  }

  if (token !== null && token !== '') {
    authStore.setToken(token)
  }
}
