<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

final readonly class ListStagesUseCase
{
    public function __construct(
        private PipelineStageRepositoryInterface $repository,
    ) {
    }

    /** @return list<PipelineStage> */
    public function execute(): array
    {
        return $this->repository->findAll();
    }
}
