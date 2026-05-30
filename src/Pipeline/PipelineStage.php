<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

/**
 * A step in the sales process for one organization (a kanban column).
 * `isWon` is true only for the terminal won stage; `isTerminal` covers won/lost.
 */
final readonly class PipelineStage
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $slug,
        public string $label,
        public int $sortOrder,
        public bool $isTerminal,
        public bool $isWon,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}
