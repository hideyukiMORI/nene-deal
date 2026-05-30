import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
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
      })
    })

    expect(ok).toBe(true)
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
