/**
 * FLEET CANON: `*_cents` is the currency's minor unit, not 1/100 of the display
 * amount. JPY has zero decimal places (ISO 4217), so `*_cents` stores whole yen
 * — never multiply by 100. ¥1,500 is stored as `1500`.
 *
 * This file follows that canon as of #81. It previously did not: `centsToYen()`
 * and `yenToCents()` divided and multiplied by 100 to compensate for Deal
 * storing x100 values, and their removal is the point of the change. There is
 * no yen⇔cents boundary any more, because for JPY there is nothing to convert.
 *
 * If you are here to add one back, you are re-opening #81 — read
 * `docs/development/coding-standards.md` first.
 */

/**
 * Format an integer JPY minor-unit amount as a localized currency string.
 * For JPY the minor unit is ¥1, so the stored value is already whole yen and
 * is formatted as-is.
 *
 * @param amountYen integer minor units (for JPY: whole yen)
 * @param locale BCP 47 locale tag (e.g. `ja`, `en`)
 */
export function formatMoneyJpy(amountYen: number, locale: string): string {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: 'JPY',
    maximumFractionDigits: 0,
  }).format(amountYen)
}
