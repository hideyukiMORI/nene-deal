import { describe, expect, it } from 'vitest'
import { formatMoneyJpy } from './format-money'

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
