import { env } from '@/shared/config/env'
import { authStore } from '@/shared/auth'
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
  const headers: Record<string, string> = {}
  if (options.body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }
  if (env.orgSlug !== '') {
    headers['X-Organization-Slug'] = env.orgSlug
  }
  if (env.apiKey !== '') {
    headers['X-NENE2-API-Key'] = env.apiKey
  }
  // The Bearer token rides on both `Authorization` and `X-Authorization`.
  // Some shared-hosting front proxies (Tier A; observed on HETEML) strip the
  // standard `Authorization` header before it reaches PHP, so the backend
  // falls back to the mirror when the standard header is missing.
  const token = authStore.getToken()
  if (token !== null) {
    headers['Authorization'] = `Bearer ${token}`
    headers['X-Authorization'] = `Bearer ${token}`
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
