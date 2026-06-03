<?php

declare(strict_types=1);

namespace NeneDeal\Forecast;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealRepositoryInterface;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Pipeline\PipelineStageRepositoryInterface;

/**
 * Computes the weighted monthly forecast from open (non-terminal) deals whose
 * expected_close_date falls in the given calendar month. Approximate — not a
 * billing or revenue figure.
 */
final readonly class ComputeForecastUseCase
{
    public function __construct(
        private PipelineStageRepositoryInterface $stages,
        private DealRepositoryInterface $deals,
    ) {
    }

    /**
     * @param string $month `YYYY-MM` (assumed already validated by the caller).
     * @param ?int $closingDay Organization closing day (1–28); null = calendar month.
     */
    public function execute(string $month, ?int $closingDay = null): ForecastSummary
    {
        $period = ForecastPeriod::forMonth($month, $closingDay);
        $start = $period->start;
        $end = $period->end;

        /** @var array<string, PipelineStage> $stageById */
        $stageById = [];
        foreach ($this->stages->findAll() as $stage) {
            $stageById[$stage->id] = $stage;
        }

        /** @var array<string, list<Deal>> $byStage */
        $byStage = [];
        $openCount = 0;
        $pipelineTotal = 0;
        $weightedTotal = 0;

        foreach ($this->deals->findInMonth($start, $end) as $deal) {
            $stage = $stageById[$deal->stageId] ?? null;

            // Skip orphaned stage refs.
            if ($stage === null) {
                continue;
            }

            // Every in-month deal contributes to the per-stage breakdown so
            // terminal stages (e.g. won) expose their landed total to the UI.
            $byStage[$deal->stageId][] = $deal;

            // Open pipeline metrics exclude terminal stages (won/lost): those
            // are no longer forecastable, they have already landed.
            if ($stage->isTerminal) {
                continue;
            }

            $openCount++;
            $pipelineTotal += $deal->amountCents;
            $weightedTotal += intdiv($deal->amountCents * $deal->probabilityPercent, 100);
        }

        $buckets = [];
        foreach ($this->stages->findAll() as $stage) {
            $stageDeals = $byStage[$stage->id] ?? [];

            if ($stageDeals === []) {
                continue;
            }

            $total = 0;
            $weighted = 0;
            foreach ($stageDeals as $deal) {
                $total += $deal->amountCents;
                $weighted += intdiv($deal->amountCents * $deal->probabilityPercent, 100);
            }

            $buckets[] = new ForecastStageBucket(
                stageId: $stage->id,
                slug: $stage->slug,
                dealCount: count($stageDeals),
                totalCents: $total,
                weightedTotalCents: $weighted,
            );
        }

        return new ForecastSummary(
            month: $month,
            periodStart: $start,
            periodEnd: $end,
            openDealCount: $openCount,
            pipelineTotalCents: $pipelineTotal,
            weightedTotalCents: $weightedTotal,
            byStage: $buckets,
        );
    }
}
