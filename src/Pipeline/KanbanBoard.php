<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

/**
 * The kanban board: one ordered column per (included) pipeline stage.
 */
final readonly class KanbanBoard
{
    /** @param list<KanbanColumn> $columns */
    public function __construct(
        public array $columns,
    ) {
    }
}
