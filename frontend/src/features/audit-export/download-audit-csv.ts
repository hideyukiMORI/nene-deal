import { buildAuthHeaders } from '@/shared/auth'
import { env } from '@/shared/config/env'

/**
 * Fetches the audit-trail CSV for the inclusive date range and triggers a
 * browser download. Shares the api client's auth headers via
 * {@link buildAuthHeaders} (the export endpoint is admin-gated, and the
 * `X-Authorization` mirror is required behind header-stripping proxies; #83)
 * but returns a binary blob rather than JSON.
 *
 * NOTE (transport migration, 2026-07-14): not moved onto
 * `createNene2Transport` in this PR. `features/` is barred from importing
 * `shared/api` by the existing `import/no-restricted-paths` zone
 * (`eslint.config.js`) — only `entities/*` may call the adapter. Deal has no
 * `entities/audit` module, and this PR is scoped to a same-file-position,
 * same-surface adapter swap (no new entity, no zone-config change). Left
 * on `buildAuthHeaders` as the one remaining self-rolled mirror; tracked as
 * a follow-up (needs an `entities/audit` home or a zone exception, either of
 * which is an architecture decision out of scope here).
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
