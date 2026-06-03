<?php

declare(strict_types=1);

namespace NeneDeal\Forecast;

/**
 * Serializes a {@see ForecastSummary} to its JSON representation
 * (see the ForecastSummary schema in docs/openapi/openapi.yaml).
 */
final class ForecastResponse
{
    /** @return array<string, mixed> */
    public static function toArray(ForecastSummary $summary): array
    {
        return [
            'month' => $summary->month,
            'period_start' => $summary->periodStart,
            'period_end' => $summary->periodEnd,
            'open_deal_count' => $summary->openDealCount,
            'pipeline_total_cents' => $summary->pipelineTotalCents,
            'weighted_total_cents' => $summary->weightedTotalCents,
            'by_stage' => array_map(
                static fn (ForecastStageBucket $bucket): array => [
                    'stage_id' => $bucket->stageId,
                    'slug' => $bucket->slug,
                    'deal_count' => $bucket->dealCount,
                    'total_cents' => $bucket->totalCents,
                    'weighted_total_cents' => $bucket->weightedTotalCents,
                ],
                $summary->byStage,
            ),
        ];
    }
}
