<?php

declare(strict_types=1);

namespace NeneDeal\Forecast;

use DateTimeImmutable;
use LogicException;
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

    /** @param string $month `YYYY-MM` (assumed already validated by the caller). */
    public function execute(string $month): ForecastSummary
    {
        $startDate = DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01');

        if ($startDate === false) {
            throw new LogicException('Invalid month passed to forecast computation.');
        }

        $start = $startDate->format('Y-m-d');
        $end = $startDate->modify('last day of this month')->format('Y-m-d');

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

            // Skip deals in terminal stages (won/lost) and orphaned stage refs.
            if ($stage === null || $stage->isTerminal) {
                continue;
            }

            $byStage[$deal->stageId][] = $deal;
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
            openDealCount: $openCount,
            pipelineTotalCents: $pipelineTotal,
            weightedTotalCents: $weightedTotal,
            byStage: $buckets,
        );
    }
}
