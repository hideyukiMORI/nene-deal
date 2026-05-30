<?php

declare(strict_types=1);

namespace NeneDeal\Forecast;

/**
 * Per-stage aggregation within a monthly forecast.
 */
final readonly class ForecastStageBucket
{
    public function __construct(
        public string $stageId,
        public string $slug,
        public int $dealCount,
        public int $totalCents,
        public int $weightedTotalCents,
    ) {
    }
}
