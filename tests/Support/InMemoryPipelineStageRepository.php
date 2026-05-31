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

    public function findById(string $id): ?PipelineStage
    {
        foreach ($this->stages as $stage) {
            if ($stage->id === $id) {
                return $stage;
            }
        }

        return null;
    }

    public function save(PipelineStage $stage): void
    {
        foreach ($this->stages as $i => $existing) {
            if ($existing->id === $stage->id) {
                $this->stages[$i] = $stage;
                return;
            }
        }

        $this->stages[] = $stage;
    }

    public function delete(string $id): void
    {
        $this->stages = array_values(array_filter(
            $this->stages,
            static fn (PipelineStage $s): bool => $s->id !== $id,
        ));
    }

    public function slugExists(string $slug): bool
    {
        foreach ($this->stages as $stage) {
            if ($stage->slug === $slug) {
                return true;
            }
        }

        return false;
    }

    /** @var array<string, true> Controlled by tests via {@see addDealToStage}. */
    private array $stagesWithDeals = [];

    public function addDealToStage(string $stageId): void
    {
        $this->stagesWithDeals[$stageId] = true;
    }

    public function hasDeals(string $stageId): bool
    {
        return isset($this->stagesWithDeals[$stageId]);
    }
}
