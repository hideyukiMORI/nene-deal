<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use NeneDeal\Audit\AuditAction;

final readonly class DeleteStageUseCase
{
    public function __construct(
        private PipelineStageRepositoryInterface $stages,
        private AuditRecorderInterface $audit,
    ) {
    }

    /**
     * @throws StageNotFoundException
     * @throws StageDeletionForbiddenException
     * @throws StageHasDealsException
     */
    public function execute(string $stageId, ?string $actorUserId = null): void
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

        $this->audit->record(new AuditEvent(
            action: AuditAction::STAGE_DELETED,
            entityType: 'stage',
            entityId: $stage->id,
            actorId: $actorUserId,
            organizationId: $stage->organizationId,
            before: ['slug' => $stage->slug, 'label' => $stage->label, 'sort_order' => $stage->sortOrder],
        ));
    }
}
