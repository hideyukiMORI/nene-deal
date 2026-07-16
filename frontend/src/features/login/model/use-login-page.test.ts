import { act } from 'react'
import { afterEach, describe, expect, it } from 'vitest'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { authStore } from '@/shared/auth'
import { useLoginPage } from './use-login-page'

describe('useLoginPage', () => {
  afterEach(() => {
    authStore.clear()
  })

  it('stores the token on successful login', async () => {
    const { result } = renderHookWithProviders(() => useLoginPage())

    let ok = false
    await act(async () => {
      ok = await result.current.submit({ email: 'operator@nene-deal.test', password: 'password' })
    })

    expect(ok).toBe(true)
    expect(authStore.getToken()).toBe('header.payload.signature')
  })

  it('reports an error and stores no token on invalid credentials', async () => {
    const { result } = renderHookWithProviders(() => useLoginPage())

    let ok = true
    await act(async () => {
      ok = await result.current.submit({ email: 'operator@nene-deal.test', password: 'wrong' })
    })

    expect(ok).toBe(false)
    expect(authStore.getToken()).toBeNull()
    await act(async () => {
      await Promise.resolve()
    })
    expect(result.current.errorKey).toBe('common.error.unauthorized')
  })
})
