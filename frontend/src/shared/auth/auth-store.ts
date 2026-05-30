type Listener = () => void

let token: string | null = null
const listeners = new Set<Listener>()

/**
 * In-memory bearer-token store. Deliberately not persisted to localStorage
 * (XSS exfiltration risk; see frontend standards) — the token is lost on
 * reload and the operator logs in again. A future ADR may move to an httpOnly
 * cookie. Shared by the API client (reads) and the login feature (writes).
 */
export const authStore = {
  getToken(): string | null {
    return token
  },
  setToken(next: string | null): void {
    token = next
    for (const listener of listeners) {
      listener()
    }
  },
  clear(): void {
    authStore.setToken(null)
  },
  subscribe(listener: Listener): () => void {
    listeners.add(listener)
    return () => {
      listeners.delete(listener)
    }
  },
}
