<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

final readonly class UpdateStageUseCase
{
    public function __construct(private PipelineStageRepositoryInterface $stages)
    {
    }

    /** @throws StageNotFoundException */
    public function execute(string $stageId, UpdateStageInput $input): PipelineStage
    {
        $stage = $this->stages->findById($stageId);

        if ($stage === null) {
            throw new StageNotFoundException($stageId);
        }

        $updated = new PipelineStage(
            id: $stage->id,
            organizationId: $stage->organizationId,
            slug: $stage->slug,
            label: $input->label ?? $stage->label,
            sortOrder: $input->sortOrder ?? $stage->sortOrder,
            isTerminal: $stage->isTerminal,
            isWon: $stage->isWon,
            createdAt: $stage->createdAt,
        );

        $this->stages->save($updated);

        return $this->stages->findById($stageId) ?? $updated;
    }
}
