<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Normalizes `deals.amount_cents` to the fleet canon: `*_cents` holds the
 * currency's minor unit, and JPY has zero decimal places (ISO 4217), so the
 * column stores whole yen. Deal stored yen x100 from its 2026-05-31 scaffold
 * until now; this divides the stored values back down by 100.
 *
 * `change()` is deliberately not used — phinx cannot auto-reverse a data
 * migration. `up()`/`down()` follow the precedent set by
 * `20260531140000_update_default_operator_role_to_admin.php`.
 *
 * The pre-flight guard is not a sanity check, it is the reversibility
 * guarantee: `/100` is lossless only while every row is a multiple of 100.
 * The API accepts any non-negative integer for `amount_cents`, so rows that
 * never passed through the frontend's (now removed) x100 helper can and do
 * carry non-multiples. Those are stopped for a human decision rather than
 * silently rounded — a rounded amount of money is a wrong amount of money.
 */
final class NormalizeDealAmountToMinorUnits extends AbstractMigration
{
    public function up(): void
    {
        $this->guardDivisible();
        $this->execute('UPDATE deals SET amount_cents = amount_cents / 100');
    }

    public function down(): void
    {
        $this->execute('UPDATE deals SET amount_cents = amount_cents * 100');
    }

    private function guardDivisible(): void
    {
        $row = $this->fetchRow('SELECT COUNT(*) AS c FROM deals WHERE amount_cents % 100 <> 0');
        $offending = (int) ($row['c'] ?? 0);

        if ($offending === 0) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Refusing to migrate: %d deal row(s) have an amount_cents that is not a multiple of 100, '
            . 'so dividing by 100 would silently lose money. Inspect them with '
            . '"SELECT id, account_label, amount_cents FROM deals WHERE amount_cents %% 100 <> 0" '
            . 'and decide how each one should be carried over before re-running this migration.',
            $offending,
        ));
    }
}
