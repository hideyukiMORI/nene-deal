import { afterEach, describe, expect, it } from 'vitest'
import { authStore } from './auth-store'
import { importDemoSeat } from './demo-seat'

const SEAT_KEY = 'nene-deal-demo-seat'

describe('importDemoSeat', () => {
  afterEach(() => {
    sessionStorage.clear()
    authStore.clear()
  })

  it('adopts a parked seat token into the in-memory store', () => {
    sessionStorage.setItem(SEAT_KEY, 'seat-token-123')

    importDemoSeat()

    expect(authStore.getToken()).toBe('seat-token-123')
  })

  it('removes the parked copy so the token never outlives the boot (one-shot)', () => {
    sessionStorage.setItem(SEAT_KEY, 'seat-token-123')

    importDemoSeat()

    expect(sessionStorage.getItem(SEAT_KEY)).toBeNull()
  })

  it('is a no-op without a parked token', () => {
    importDemoSeat()

    expect(authStore.getToken()).toBeNull()
  })

  it('ignores an empty parked value but still clears it', () => {
    sessionStorage.setItem(SEAT_KEY, '')

    importDemoSeat()

    expect(authStore.getToken()).toBeNull()
    expect(sessionStorage.getItem(SEAT_KEY)).toBeNull()
  })
})
