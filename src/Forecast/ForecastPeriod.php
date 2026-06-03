<?php

declare(strict_types=1);

namespace NeneDeal\Forecast;

use DateTimeImmutable;
use LogicException;

/**
 * The date window a forecast covers, resolved from an organization's closing
 * day. `closingDay` null means a calendar month (1st–last day). A value 1–28
 * means the window runs from the day after the previous month's closing day
 * through this month's closing day (e.g. closing day 20 → 21st–20th).
 *
 * `month` is the YYYY-MM label the window closes in.
 */
final readonly class ForecastPeriod
{
    public function __construct(
        public string $month,
        public string $start,
        public string $end,
    ) {
    }

    /** Window whose closing day falls in the given YYYY-MM month. */
    public static function forMonth(string $month, ?int $closingDay): self
    {
        $monthStart = DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01');
        if ($monthStart === false) {
            throw new LogicException('Invalid month passed to forecast period.');
        }

        if ($closingDay === null) {
            return new self(
                $month,
                $monthStart->format('Y-m-d'),
                $monthStart->modify('last day of this month')->format('Y-m-d'),
            );
        }

        // End = closing day of this month (clamped to month length).
        $end = self::clampDay($monthStart, $closingDay);
        // Start = day after the previous month's closing day.
        $prevEnd = self::clampDay($monthStart->modify('first day of previous month'), $closingDay);
        $start = $prevEnd->modify('+1 day');

        return new self($month, $start->format('Y-m-d'), $end->format('Y-m-d'));
    }

    /** The window that contains $today, given the closing day. */
    public static function current(?int $closingDay, DateTimeImmutable $today): self
    {
        if ($closingDay === null) {
            return self::forMonth($today->format('Y-m'), null);
        }

        // If today is past this month's closing day, the open window closes next month.
        $month = (int) $today->format('d') <= $closingDay
            ? $today->format('Y-m')
            : $today->modify('first day of next month')->format('Y-m');

        return self::forMonth($month, $closingDay);
    }

    private static function clampDay(DateTimeImmutable $monthStart, int $day): DateTimeImmutable
    {
        $lastDay = (int) $monthStart->modify('last day of this month')->format('d');
        $clamped = min($day, $lastDay);

        return $monthStart->setDate(
            (int) $monthStart->format('Y'),
            (int) $monthStart->format('m'),
            $clamped,
        );
    }
}
