import {
  createNene2Transport,
  isNene2ClientError,
  isValidationProblemDetails,
  type Nene2ClientError,
  type TokenStore,
} from '@hideyukimori/nene2-client'
import { env } from '@/shared/config/env'
import { AppError, type ProblemDetails } from '@/shared/api/errors'
import { authStore } from '@/shared/auth'

/**
 * Adapts deal's existing in-memory `authStore` (deliberately not persisted —
 * see `shared/auth/auth-store.ts`) to the transport's minimal `TokenStore`
 * contract. Deal keeps its memory-only session on purpose (no reload
 * survival), so this does *not* switch to the fleet-default
 * `createSessionTokenStore` (sessionStorage) — same adapter pattern the
 * migration guide documents for vault's session object
 * (`migrate-product-client.md`).
 */
const tokenStore: TokenStore = {
  getToken: () => authStore.getToken(),
  clearToken: () => {
    authStore.clear()
  },
}

/**
 * Fleet-standard transport (`@hideyukimori/nene2-client`, issue #102): every
 * request mirrors the bearer token onto `Authorization` *and*
 * `X-Authorization` so shared-hosting proxies that strip the standard header
 * still authenticate (HETEML; #67/#68) — this is the packaged form of deal's
 * own choke point (`buildAuthHeaders`, now removed here; the package's
 * `headers.ts` doc comment credits deal's #83 by name as the origin pattern).
 * `apiClient` below is a thin adapter that keeps this product's existing
 * surface (`get/post/patch/delete`) verbatim so call sites did not need to
 * change. (The audit CSV export in `features/audit-export/` is not routed
 * through this transport yet — see that file's note — so `getBlob` is not
 * added here per A-2's "add only with real consumption".)
 */
const transport = createNene2Transport({
  baseUrl: env.apiBaseUrl,
  tokenStore,
  // Only set when non-empty — matches the removed `buildAuthHeaders()`,
  // which omitted the header entirely for single-tenant/open deployments
  // rather than sending it with an empty value.
  headers: env.orgSlug !== '' ? { 'X-Organization-Slug': env.orgSlug } : {},
  apiKey: env.apiKey !== '' ? env.apiKey : undefined,
  credentials: 'include',
  // Look up `fetch` at call time (not bind it once at module load): tests
  // patch `globalThis.fetch` via msw's `server.listen()`, which can run
  // after this module is first imported.
  fetch: (input, init) => globalThis.fetch(input, init),
})

/** Maps the package's `Nene2ClientError` to this product's `AppError` (unchanged public shape/behavior for callers). */
function toAppError(error: Nene2ClientError): AppError {
  const problem = error.problem
  if (problem === undefined) {
    return new AppError({
      type: 'about:blank',
      title: error.message !== '' ? error.message : 'Request failed',
      status: error.status,
      instance: error.url,
    })
  }

  const mapped: ProblemDetails = {
    type: problem.type,
    title: problem.title,
    status: problem.status,
    instance: problem.instance ?? error.url,
  }
  if (problem.detail !== undefined) {
    mapped.detail = problem.detail
  }
  if (isValidationProblemDetails(problem)) {
    mapped.errors = problem.errors
  }
  return new AppError(mapped)
}

async function unwrap<T>(promise: Promise<T>): Promise<T> {
  try {
    return await promise
  } catch (error) {
    if (isNene2ClientError(error)) {
      throw toAppError(error)
    }
    throw error
  }
}

export const apiClient = {
  get<T>(path: string, signal?: AbortSignal): Promise<T> {
    return unwrap(transport.get<T>(path, signal !== undefined ? { signal } : {}))
  },
  post<T>(path: string, body: unknown): Promise<T> {
    return unwrap(transport.post<T>(path, body))
  },
  patch<T>(path: string, body: unknown): Promise<T> {
    return unwrap(transport.patch<T>(path, body))
  },
  delete(path: string): Promise<undefined> {
    return unwrap(transport.delete<undefined>(path))
  },
}

export { AppError }
