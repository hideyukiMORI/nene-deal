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
