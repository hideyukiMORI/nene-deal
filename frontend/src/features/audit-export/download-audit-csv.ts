import { buildAuthHeaders } from '@/shared/auth'
import { env } from '@/shared/config/env'

/**
 * Fetches the audit-trail CSV for the inclusive date range and triggers a
 * browser download. Shares the api client's auth headers via
 * {@link buildAuthHeaders} (the export endpoint is admin-gated, and the
 * `X-Authorization` mirror is required behind header-stripping proxies; #83)
 * but returns a binary blob rather than JSON.
 *
 * @throws Error when the request fails (caller surfaces a toast).
 */
export async function downloadAuditCsv(from: string, to: string): Promise<void> {
  const base = env.apiBaseUrl.replace(/\/$/, '')
  const params = new URLSearchParams({ from, to })

  const response = await fetch(`${base}/api/v1/audit/export?${params.toString()}`, {
    headers: buildAuthHeaders(),
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
