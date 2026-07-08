<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use DateTimeImmutable;
use Nene2\Http\ClockInterface;

/**
 * Test {@see ClockInterface} that always returns a fixed instant, so
 * time-dependent behaviour (token iat/exp, created_at/updated_at stamps,
 * the current forecast period) is deterministic.
 */
final readonly class FixedClock implements ClockInterface
{
    public const DEFAULT_INSTANT = '2026-06-01T10:00:00+00:00';

    public function __construct(private string $instant = self::DEFAULT_INSTANT)
    {
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->instant);
    }
}
