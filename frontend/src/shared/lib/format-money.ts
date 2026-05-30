/**
 * Format an integer JPY minor-unit (cents) amount as a localized currency
 * string. Amounts are stored as cents (1/100 yen) across the API; display
 * rounds to whole yen.
 *
 * @param amountCents integer minor units (JPY cents)
 * @param locale BCP 47 locale tag (e.g. `ja`, `en`)
 */
export function formatMoneyJpy(amountCents: number, locale: string): string {
  const yen = Math.round(amountCents / 100)
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: 'JPY',
    maximumFractionDigits: 0,
  }).format(yen)
}
