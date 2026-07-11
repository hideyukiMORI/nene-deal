<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use DateTimeImmutable;
use Nene2\Http\ClockInterface;

/** A clock the test can advance, to exercise window/lock expiry. */
final class MutableClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct(string $start = '2026-06-01T10:00:00+00:00')
    {
        $this->now = new DateTimeImmutable($start);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(int $seconds): void
    {
        $this->now = $this->now->modify("+{$seconds} seconds");
    }
}
