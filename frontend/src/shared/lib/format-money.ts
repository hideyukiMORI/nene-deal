/**
 * FLEET CANON: `*_cents` is the currency's minor unit, not 1/100 of the display
 * amount. JPY has zero decimal places (ISO 4217), so `*_cents` stores whole yen
 * — never multiply by 100. ¥1,500 is stored as `1500`.
 *
 * THIS FILE DOES NOT FOLLOW THAT CANON TODAY. Deal stores x100 values, and the
 * helpers below divide and multiply by 100 to compensate. That is a known
 * deviation, not the standard. Correction is tracked separately as the fleet
 * money-unit remediation (order 1 of: 0. define -> 1. deal -> 2. serve ->
 * 3. clear); background in `_work/reports/2026-08-20-money-unit-archaeology.md`.
 *
 * DO NOT COPY THIS FILE AS A TEMPLATE. `nene-serve`'s copy inherited the same
 * deviation verbatim — its first six lines are identical to this file's.
 */

/**
 * Format an integer JPY minor-unit (cents) amount as a localized currency
 * string. Amounts are stored as cents (1/100 yen) across the API; display
 * rounds to whole yen.
 *
 * @param amountCents integer minor units (JPY cents)
 * @param locale BCP 47 locale tag (e.g. `ja`, `en`)
 */
export function formatMoneyJpy(amountCents: number, locale: string): string {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: 'JPY',
    maximumFractionDigits: 0,
  }).format(centsToYen(amountCents))
}

/**
 * Convert an integer cents amount to whole yen for form display.
 * Rounding matches {@link formatMoneyJpy} (sub-yen amounts do not occur).
 */
export function centsToYen(amountCents: number): number {
  return Math.round(amountCents / 100)
}

/**
 * Convert a whole-yen form input back to integer cents for the API.
 * Inputs are validated as integers; rounding guards against float artifacts.
 */
export function yenToCents(amountYen: number): number {
  return Math.round(amountYen * 100)
}
