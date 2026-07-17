import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { afterEach, describe, expect, it } from 'vitest'
import { http, HttpResponse } from 'msw'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { MONTH_END, useSettingsPage } from './use-settings-page'

interface SettingsDto {
  forecast_closing_day: number | null
}

function stubSettings(initial: number | null): { lastPatch: () => number | null | undefined } {
  let current: number | null = initial
  let sawPatch = false
  mswServer.use(
    http.get('/api/v1/settings', () =>
      HttpResponse.json<SettingsDto>({ forecast_closing_day: current }),
    ),
    http.patch('/api/v1/settings', async ({ request }) => {
      const body = (await request.json()) as SettingsDto
      current = body.forecast_closing_day
      sawPatch = true
      return HttpResponse.json<SettingsDto>({ forecast_closing_day: current })
    }),
  )
  return { lastPatch: () => (sawPatch ? current : undefined) }
}

describe('useSettingsPage', () => {
  afterEach(() => {
    mswServer.resetHandlers()
  })

  it('maps a numeric closing day to its string option once loaded', async () => {
    stubSettings(15)
    const { result } = renderHookWithProviders(() => useSettingsPage())

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })
    expect(result.current.value).toBe('15')
  })

  it('falls back to the month-end sentinel when the closing day is null', async () => {
    stubSettings(null)
    const { result } = renderHookWithProviders(() => useSettingsPage())

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })
    expect(result.current.value).toBe(MONTH_END)
  })

  it('layers a local draft over the loaded value', async () => {
    stubSettings(10)
    const { result } = renderHookWithProviders(() => useSettingsPage())

    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    act(() => {
      result.current.setDraft('20')
    })
    expect(result.current.value).toBe('20')
  })

  it('persists a numeric selection as a number and resolves true', async () => {
    const probe = stubSettings(null)
    const { result } = renderHookWithProviders(() => useSettingsPage())
    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    act(() => {
      result.current.setDraft('7')
    })

    let ok = false
    await act(async () => {
      ok = await result.current.save()
    })

    expect(ok).toBe(true)
    expect(probe.lastPatch()).toBe(7)
  })

  it('persists the month-end sentinel as null', async () => {
    const probe = stubSettings(12)
    const { result } = renderHookWithProviders(() => useSettingsPage())
    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    act(() => {
      result.current.setDraft(MONTH_END)
    })

    let ok = false
    await act(async () => {
      ok = await result.current.save()
    })

    expect(ok).toBe(true)
    expect(probe.lastPatch()).toBeNull()
  })

  it('resolves false when the save request fails', async () => {
    mswServer.use(
      http.get('/api/v1/settings', () =>
        HttpResponse.json<SettingsDto>({ forecast_closing_day: null }),
      ),
      http.patch('/api/v1/settings', () => new HttpResponse(null, { status: 500 })),
    )
    const { result } = renderHookWithProviders(() => useSettingsPage())
    await waitFor(() => {
      expect(result.current.loading).toBe(false)
    })

    let ok = true
    await act(async () => {
      ok = await result.current.save()
    })

    expect(ok).toBe(false)
  })
})
