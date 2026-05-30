<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealRepositoryInterface;

/**
 * Builds the kanban board: groups the organization's deals into stage columns.
 * Terminal stages (won/lost) are excluded unless `includeTerminal` is true.
 */
final readonly class BuildBoardUseCase
{
    public function __construct(
        private PipelineStageRepositoryInterface $stages,
        private DealRepositoryInterface $deals,
    ) {
    }

    public function execute(?string $ownerUserId, bool $includeTerminal): KanbanBoard
    {
        /** @var array<string, list<Deal>> $byStage */
        $byStage = [];
        foreach ($this->deals->findForBoard($ownerUserId) as $deal) {
            $byStage[$deal->stageId][] = $deal;
        }

        $columns = [];
        foreach ($this->stages->findAll() as $stage) {
            if ($stage->isTerminal && !$includeTerminal) {
                continue;
            }

            $stageDeals = $byStage[$stage->id] ?? [];

            $total = 0;
            $weighted = 0;
            foreach ($stageDeals as $deal) {
                $total += $deal->amountCents;
                $weighted += intdiv($deal->amountCents * $deal->probabilityPercent, 100);
            }

            $columns[] = new KanbanColumn(
                stage: $stage,
                deals: $stageDeals,
                dealCount: count($stageDeals),
                totalCents: $total,
                weightedTotalCents: $weighted,
            );
        }

        return new KanbanBoard($columns);
    }
}
