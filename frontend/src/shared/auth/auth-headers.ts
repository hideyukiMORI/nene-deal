import { env } from '@/shared/config/env'
import { authStore } from './auth-store'

/**
 * Builds the auth headers every backend request must carry: the organization
 * slug, the machine API key, and — when logged in — the Bearer token.
 *
 * The Bearer token rides on both `Authorization` and `X-Authorization`.
 * Some shared-hosting front proxies (Tier A; observed on HETEML) strip the
 * standard `Authorization` header before it reaches PHP, so the backend falls
 * back to the mirror when the standard header is missing (#67/#68).
 *
 * Every fetch path — the JSON api client and binary downloads alike — must
 * build its auth headers here rather than hand-rolling them, so no caller can
 * drop the `X-Authorization` mirror again (#83).
 */
export function buildAuthHeaders(): Record<string, string> {
  const headers: Record<string, string> = {}
  if (env.orgSlug !== '') {
    headers['X-Organization-Slug'] = env.orgSlug
  }
  if (env.apiKey !== '') {
    headers['X-NENE2-API-Key'] = env.apiKey
  }
  const token = authStore.getToken()
  if (token !== null) {
    headers['Authorization'] = `Bearer ${token}`
    headers['X-Authorization'] = `Bearer ${token}`
  }
  return headers
}
