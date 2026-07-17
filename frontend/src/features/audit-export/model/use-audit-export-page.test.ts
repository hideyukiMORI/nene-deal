import { act } from 'react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { http, HttpResponse } from 'msw'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { authStore } from '@/shared/auth'
import { useAuditExportPage } from './use-audit-export-page'

const CSV_BODY = 'timestamp,actor,action,deal_id,field,before,after\n'
const TOKEN = 'header.payload.signature'

describe('useAuditExportPage', () => {
  beforeEach(() => {
    authStore.setToken(TOKEN)
    // jsdom implements neither object URLs nor real navigation (mirrors
    // download-audit-csv.test.ts, since download() drives that same path).
    URL.createObjectURL = vi.fn(() => 'blob:mock')
    URL.revokeObjectURL = vi.fn()
    vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => undefined)
  })

  afterEach(() => {
    authStore.clear()
    vi.restoreAllMocks()
  })

  it('defaults the range to the first of the month through today', () => {
    const { result } = renderHookWithProviders(() => useAuditExportPage())

    const today = new Date().toISOString().slice(0, 10)
    expect(result.current.from).toBe(`${today.slice(0, 7)}-01`)
    expect(result.current.to).toBe(today)
    expect(result.current.invalidRange).toBe(false)
    expect(result.current.pending).toBe(false)
  })

  it('flags an inverted range as invalid', () => {
    const { result } = renderHookWithProviders(() => useAuditExportPage())

    act(() => {
      result.current.setFrom('2026-07-31')
      result.current.setTo('2026-07-01')
    })

    expect(result.current.invalidRange).toBe(true)
  })

  it('downloads the CSV for the current range and resolves true', async () => {
    let requestedUrl = ''
    mswServer.use(
      http.get('/api/v1/audit/export', ({ request }) => {
        requestedUrl = request.url
        return new HttpResponse(CSV_BODY, { headers: { 'Content-Type': 'text/csv' } })
      }),
    )

    const { result } = renderHookWithProviders(() => useAuditExportPage())
    act(() => {
      result.current.setFrom('2026-07-01')
      result.current.setTo('2026-07-11')
    })

    let ok = false
    await act(async () => {
      ok = await result.current.download()
    })

    expect(ok).toBe(true)
    const url = new URL(requestedUrl)
    expect(url.searchParams.get('from')).toBe('2026-07-01')
    expect(url.searchParams.get('to')).toBe('2026-07-11')
    expect(result.current.pending).toBe(false)
  })

  it('resolves false when the export request fails', async () => {
    mswServer.use(http.get('/api/v1/audit/export', () => new HttpResponse(null, { status: 401 })))

    const { result } = renderHookWithProviders(() => useAuditExportPage())

    let ok = true
    await act(async () => {
      ok = await result.current.download()
    })

    expect(ok).toBe(false)
    expect(result.current.pending).toBe(false)
  })

  it('does not attempt a download for an invalid range', async () => {
    let called = false
    mswServer.use(
      http.get('/api/v1/audit/export', () => {
        called = true
        return new HttpResponse(CSV_BODY, { headers: { 'Content-Type': 'text/csv' } })
      }),
    )

    const { result } = renderHookWithProviders(() => useAuditExportPage())
    act(() => {
      result.current.setFrom('2026-07-31')
      result.current.setTo('2026-07-01')
    })

    let ok = true
    await act(async () => {
      ok = await result.current.download()
    })

    expect(ok).toBe(false)
    expect(called).toBe(false)
  })
})
