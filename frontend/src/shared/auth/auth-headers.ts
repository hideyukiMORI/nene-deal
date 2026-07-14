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
 * The JSON api client (`shared/api/client.ts`) moved onto
 * `@hideyukimori/nene2-client`'s `createNene2Transport` (issue #102), which
 * packages this same choke-point pattern — see that file's header comment.
 * This function is kept only for `features/audit-export/download-audit-csv.ts`,
 * the one remaining caller that cannot import `shared/api` (ESLint zone;
 * see that file's note) without a layout change out of scope for the
 * transport migration.
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
