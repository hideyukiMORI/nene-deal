import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useDealDetailPage } from './use-deal-detail-page'

const DEAL_ID = '01DEALACME000000000000000A'

describe('useDealDetailPage', () => {
  it('loads the deal from the API', async () => {
    const { result } = renderHookWithProviders(() => useDealDetailPage(DEAL_ID))

    await waitFor(() => {
      expect(result.current.status).toBe('ready')
    })

    expect(result.current.deal?.accountLabel).toBe('Acme Corp')
  })

  it('reports missing status when no id is provided', () => {
    const { result } = renderHookWithProviders(() => useDealDetailPage(undefined))
    expect(result.current.status).toBe('missing')
  })

  it('edits the deal and reports success', async () => {
    const { result } = renderHookWithProviders(() => useDealDetailPage(DEAL_ID))
    await waitFor(() => {
      expect(result.current.status).toBe('ready')
    })

    let ok = false
    await act(async () => {
      ok = await result.current.submitEdit({
        accountLabel: 'Acme Renamed',
        amountCents: 200_000_000,
        probabilityPercent: 100,
        note: 'updated',
        expectedCloseDate: null,
      })
    })

    expect(ok).toBe(true)
  })

  it('does not refetch the deleted deal after delete (issue #84)', async () => {
    // Count GET requests for the deal detail; assert the delete path fires none
    // after the resource is gone (otherwise a 404 lands in the console).
    const detailGets: string[] = []
    const detailPath = /^\/api\/v1\/deals\/[^/]+$/
    const onRequest = ({ request }: { request: Request }) => {
      const { pathname } = new URL(request.url)
      if (request.method === 'GET' && detailPath.test(pathname)) detailGets.push(pathname)
    }
    mswServer.events.on('request:start', onRequest)
    mswServer.use(
      http.get('/api/v1/deals/:id/history', () => HttpResponse.json({ data: [] })),
      http.delete('/api/v1/deals/:id', () => new HttpResponse(null, { status: 204 })),
    )

    try {
      const { result } = renderHookWithProviders(() => useDealDetailPage(DEAL_ID))
      await waitFor(() => {
        expect(result.current.status).toBe('ready')
      })

      const getsBeforeDelete = detailGets.length
      expect(getsBeforeDelete).toBeGreaterThan(0)

      let ok = false
      await act(async () => {
        ok = await result.current.deleteDeal()
      })
      expect(ok).toBe(true)

      // Give any erroneous refetch a chance to fire before asserting.
      await new Promise((resolve) => setTimeout(resolve, 50))

      expect(detailGets.length).toBe(getsBeforeDelete)
    } finally {
      mswServer.events.removeListener('request:start', onRequest)
    }
  })

  it('hands off to Invoice and exposes the link ids', async () => {
    const { result } = renderHookWithProviders(() => useDealDetailPage(DEAL_ID))
    await waitFor(() => {
      expect(result.current.status).toBe('ready')
    })

    let ok = false
    await act(async () => {
      ok = await result.current.handoff()
    })

    expect(ok).toBe(true)
    await waitFor(() => {
      expect(result.current.handoffResult?.invoiceQuoteId).toBe(9930)
    })
  })
})
