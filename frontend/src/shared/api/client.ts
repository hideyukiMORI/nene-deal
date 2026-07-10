import { env } from '@/shared/config/env'
import { authStore, buildAuthHeaders } from '@/shared/auth'
import { AppError, parseProblemDetails } from '@/shared/api/errors'

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

interface RequestOptions {
  method?: HttpMethod
  body?: unknown
  signal?: AbortSignal
}

/**
 * Single transport surface. No domain logic: builds the request, attaches JSON
 * headers, parses the body, and throws {@link AppError} from Problem Details on
 * non-2xx. Authentication is added here when it lands (single-org for now).
 */
async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const base = env.apiBaseUrl.replace(/\/$/, '')
  // Auth headers (org slug, API key, Bearer + X-Authorization mirror for
  // header-stripping proxies) come from the shared builder — see
  // `auth-headers.ts` for the rationale.
  const headers: Record<string, string> = buildAuthHeaders()
  if (options.body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }

  const response = await fetch(`${base}${path}`, {
    method: options.method ?? 'GET',
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    credentials: 'include',
    signal: options.signal,
  })

  if (!response.ok) {
    // An expired/invalid session clears the token; the auth gate reacts and
    // routes back to login. The login request itself is exempt.
    if (response.status === 401 && !path.includes('/auth/login')) {
      authStore.clear()
    }
    throw await parseProblemDetails(response)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return (await response.json()) as T
}

export const apiClient = {
  get<T>(path: string, signal?: AbortSignal): Promise<T> {
    return request<T>(path, signal !== undefined ? { signal } : {})
  },
  post<T>(path: string, body: unknown): Promise<T> {
    return request<T>(path, { method: 'POST', body })
  },
  patch<T>(path: string, body: unknown): Promise<T> {
    return request<T>(path, { method: 'PATCH', body })
  },
  delete(path: string): Promise<undefined> {
    return request<undefined>(path, { method: 'DELETE' })
  },
}

export { AppError }
