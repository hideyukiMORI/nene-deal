import { authStore } from '@/shared/auth'
import { env } from '@/shared/config/env'

/**
 * Fetches the audit-trail CSV for the inclusive date range and triggers a
 * browser download. Mirrors the api client's auth headers (the export endpoint
 * is admin-gated) but returns a binary blob rather than JSON.
 *
 * @throws Error when the request fails (caller surfaces a toast).
 */
export async function downloadAuditCsv(from: string, to: string): Promise<void> {
  const base = env.apiBaseUrl.replace(/\/$/, '')
  const params = new URLSearchParams({ from, to })

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
  }

  const response = await fetch(`${base}/api/v1/audit/export?${params.toString()}`, {
    headers,
    credentials: 'include',
  })

  if (!response.ok) {
    throw new Error(`Audit export failed with status ${String(response.status)}`)
  }

  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = `audit-${from}_${to}.csv`
  document.body.appendChild(anchor)
  anchor.click()
  anchor.remove()
  URL.revokeObjectURL(url)
}
