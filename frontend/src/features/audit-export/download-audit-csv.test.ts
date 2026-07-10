import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { http, HttpResponse } from 'msw'
import { mswServer } from '@tests/msw/server'
import { authStore } from '@/shared/auth'
import { downloadAuditCsv } from './download-audit-csv'

const CSV_BODY = 'timestamp,actor,action,deal_id,field,before,after\n'
const TOKEN = 'header.payload.signature'

describe('downloadAuditCsv', () => {
  beforeEach(() => {
    authStore.setToken(TOKEN)
    // jsdom implements neither object URLs nor real navigation.
    URL.createObjectURL = vi.fn(() => 'blob:mock')
    URL.revokeObjectURL = vi.fn()
    vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => undefined)
  })

  afterEach(() => {
    authStore.clear()
    vi.restoreAllMocks()
  })

  it('sends the Bearer token on both Authorization and X-Authorization (#83)', async () => {
    // Regression guard: some shared-hosting front proxies (HETEML) strip the
    // standard `Authorization` header, so the backend falls back to the
    // `X-Authorization` mirror (#67/#68). The CSV download path once built its
    // headers by hand and dropped the mirror, producing production-only 401s.
    let captured: Headers | null = null
    mswServer.use(
      http.get('/api/v1/audit/export', ({ request }) => {
        captured = request.headers
        return new HttpResponse(CSV_BODY, {
          headers: { 'Content-Type': 'text/csv' },
        })
      }),
    )

    await downloadAuditCsv('2026-07-01', '2026-07-11')

    expect(captured).not.toBeNull()
    const headers = captured as unknown as Headers
    expect(headers.get('Authorization')).toBe(`Bearer ${TOKEN}`)
    expect(headers.get('X-Authorization')).toBe(`Bearer ${TOKEN}`)
  })

  it('passes the inclusive date range as from/to query params', async () => {
    let requestedUrl = ''
    mswServer.use(
      http.get('/api/v1/audit/export', ({ request }) => {
        requestedUrl = request.url
        return new HttpResponse(CSV_BODY, {
          headers: { 'Content-Type': 'text/csv' },
        })
      }),
    )

    await downloadAuditCsv('2026-07-01', '2026-07-11')

    const url = new URL(requestedUrl)
    expect(url.searchParams.get('from')).toBe('2026-07-01')
    expect(url.searchParams.get('to')).toBe('2026-07-11')
  })

  it('throws with the status code when the export fails', async () => {
    mswServer.use(http.get('/api/v1/audit/export', () => new HttpResponse(null, { status: 401 })))

    await expect(downloadAuditCsv('2026-07-01', '2026-07-11')).rejects.toThrow(
      'Audit export failed with status 401',
    )
  })
})
