<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

final readonly class DeleteStageUseCase
{
    public function __construct(private PipelineStageRepositoryInterface $stages)
    {
    }

    /**
     * @throws StageNotFoundException
     * @throws StageDeletionForbiddenException
     * @throws StageHasDealsException
     */
    public function execute(string $stageId): void
    {
        $stage = $this->stages->findById($stageId);

        if ($stage === null) {
            throw new StageNotFoundException($stageId);
        }

        if ($stage->isTerminal) {
            throw new StageDeletionForbiddenException($stageId);
        }

        if ($this->stages->hasDeals($stageId)) {
            throw new StageHasDealsException($stageId);
        }

        $this->stages->delete($stageId);
    }
}
