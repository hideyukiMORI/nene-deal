<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Pipeline\PipelineStageRepositoryInterface;

final class InMemoryPipelineStageRepository implements PipelineStageRepositoryInterface
{
    /** @var list<PipelineStage> */
    private array $stages;

    /** @param list<PipelineStage> $stages */
    public function __construct(array $stages = [])
    {
        $this->stages = $stages;
    }

    /** @return list<PipelineStage> */
    public function findAll(): array
    {
        $sorted = $this->stages;
        usort($sorted, static fn (PipelineStage $a, PipelineStage $b): int => $a->sortOrder <=> $b->sortOrder);

        return $sorted;
    }

    public function findByIdOrSlug(string $idOrSlug): ?PipelineStage
    {
        foreach ($this->stages as $stage) {
            if ($stage->id === $idOrSlug || $stage->slug === $idOrSlug) {
                return $stage;
            }
        }

        return null;
    }
}
