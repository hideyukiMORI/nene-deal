import { describe, expect, it } from 'vitest'
import * as formatMoney from './format-money'
import { formatMoneyJpy } from './format-money'

describe('formatMoneyJpy', () => {
  it('formats the stored value as whole yen without dividing', () => {
    expect(formatMoneyJpy(1_500_000, 'en')).toContain('1,500,000')
  })

  it('formats a value that is not a multiple of 100 exactly', () => {
    // The old x100 reading could not represent this: it would have rendered
    // ¥1,235. Under the canon, 123_456 is ¥123,456.
    expect(formatMoneyJpy(123_456, 'en')).toContain('123,456')
  })

  it('renders zero', () => {
    expect(formatMoneyJpy(0, 'en')).toContain('0')
  })
})

describe('yen⇔cents conversion boundary (positive control for #81)', () => {
  // This suite guards an ABSENCE, so it needs a control that can actually fail.
  // Asserting "the amount round-trips unchanged" would be trivially true once
  // the converters are gone, and would stay green if someone re-added them and
  // wired them in elsewhere. Asserting the module surface does not.
  it('does not export a yen→cents converter', () => {
    expect(formatMoney).not.toHaveProperty('yenToCents')
  })

  it('does not export a cents→yen converter', () => {
    expect(formatMoney).not.toHaveProperty('centsToYen')
  })

  it('exports formatMoneyJpy only', () => {
    // The control that proves the two assertions above are reachable: if the
    // module were empty or failed to load, `not.toHaveProperty` would pass for
    // the wrong reason.
    expect(Object.keys(formatMoney)).toEqual(['formatMoneyJpy'])
  })
})
