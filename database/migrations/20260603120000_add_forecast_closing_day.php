<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Organization-level forecast closing day. NULL = calendar month (month-end),
 * which preserves the existing behaviour. A value 1–28 makes the forecast
 * period run from the day after the closing day of the previous month through
 * the closing day of the period's month (e.g. 20 → 21st–20th).
 *
 * This only shifts the sales-forecast aggregation window. Billing close dates
 * remain out of scope (they live in NeNe Invoice — see ADR 0002).
 */
final class AddForecastClosingDay extends AbstractMigration
{
    public function change(): void
    {
        $this->table('organizations')
            ->addColumn('forecast_closing_day', 'integer', ['null' => true, 'default' => null])
            ->update();
    }
}
