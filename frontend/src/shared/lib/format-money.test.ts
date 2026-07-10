import { describe, expect, it } from 'vitest'
import { centsToYen, formatMoneyJpy, yenToCents } from './format-money'

describe('formatMoneyJpy', () => {
  it('converts cents to whole yen and groups thousands', () => {
    expect(formatMoneyJpy(150_000_000, 'en')).toContain('1,500,000')
  })

  it('rounds to whole yen', () => {
    expect(formatMoneyJpy(149, 'en')).toContain('1')
  })

  it('renders zero', () => {
    expect(formatMoneyJpy(0, 'en')).toContain('0')
  })
})

describe('centsToYen', () => {
  it('converts cents to whole yen', () => {
    expect(centsToYen(62_000_000)).toBe(620_000)
  })

  it('rounds like formatMoneyJpy', () => {
    expect(centsToYen(149)).toBe(1)
    expect(centsToYen(150)).toBe(2)
  })
})

describe('yenToCents', () => {
  it('converts whole yen to cents', () => {
    expect(yenToCents(620_000)).toBe(62_000_000)
    expect(yenToCents(0)).toBe(0)
  })
})
