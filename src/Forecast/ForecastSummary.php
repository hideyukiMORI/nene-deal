<?php

declare(strict_types=1);

namespace NeneDeal\Forecast;

/**
 * Approximate weighted landing for a calendar month (see scope-contract D4).
 */
final readonly class ForecastSummary
{
    /** @param list<ForecastStageBucket> $byStage */
    public function __construct(
        public string $month,
        public string $periodStart,
        public string $periodEnd,
        public int $openDealCount,
        public int $pipelineTotalCents,
        public int $weightedTotalCents,
        public array $byStage,
    ) {
    }
}
