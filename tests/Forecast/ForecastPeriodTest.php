<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Forecast;

use DateTimeImmutable;
use NeneDeal\Forecast\ForecastPeriod;
use PHPUnit\Framework\TestCase;

final class ForecastPeriodTest extends TestCase
{
    public function test_null_closing_day_is_the_calendar_month(): void
    {
        $p = ForecastPeriod::forMonth('2026-06', null);
        self::assertSame('2026-06', $p->month);
        self::assertSame('2026-06-01', $p->start);
        self::assertSame('2026-06-30', $p->end);
    }

    public function test_closing_day_20_runs_21st_to_20th(): void
    {
        $p = ForecastPeriod::forMonth('2026-06', 20);
        self::assertSame('2026-05-21', $p->start);
        self::assertSame('2026-06-20', $p->end);
    }

    public function test_closing_day_clamps_around_short_previous_month(): void
    {
        // Closing day 28; previous month February has 28 days, so the window
        // starts the day after 2026-02-28.
        $p = ForecastPeriod::forMonth('2026-03', 28);
        self::assertSame('2026-03-01', $p->start);
        self::assertSame('2026-03-28', $p->end);
    }

    public function test_current_before_closing_day_uses_this_month(): void
    {
        $p = ForecastPeriod::current(20, new DateTimeImmutable('2026-06-10'));
        self::assertSame('2026-06', $p->month);
        self::assertSame('2026-05-21', $p->start);
        self::assertSame('2026-06-20', $p->end);
    }

    public function test_current_after_closing_day_rolls_to_next_month(): void
    {
        $p = ForecastPeriod::current(20, new DateTimeImmutable('2026-06-25'));
        self::assertSame('2026-07', $p->month);
        self::assertSame('2026-06-21', $p->start);
        self::assertSame('2026-07-20', $p->end);
    }
}
