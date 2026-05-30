<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use NeneDeal\Deal\Deal;

/**
 * One kanban column: a stage with its deals and per-column totals.
 */
final readonly class KanbanColumn
{
    /** @param list<Deal> $deals */
    public function __construct(
        public PipelineStage $stage,
        public array $deals,
        public int $dealCount,
        public int $totalCents,
        public int $weightedTotalCents,
    ) {
    }
}
