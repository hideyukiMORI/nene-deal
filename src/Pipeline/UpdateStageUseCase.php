<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use NeneDeal\Audit\AuditAction;

final readonly class UpdateStageUseCase
{
    public function __construct(
        private PipelineStageRepositoryInterface $stages,
        private AuditRecorderInterface $audit,
    ) {
    }

    /** @throws StageNotFoundException */
    public function execute(string $stageId, UpdateStageInput $input, ?string $actorUserId = null): PipelineStage
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

        $this->audit->record(new AuditEvent(
            action: AuditAction::STAGE_UPDATED,
            entityType: 'stage',
            entityId: $stage->id,
            actorId: $actorUserId,
            organizationId: $stage->organizationId,
            before: ['label' => $stage->label, 'sort_order' => $stage->sortOrder],
            after: ['label' => $updated->label, 'sort_order' => $updated->sortOrder],
        ));

        return $this->stages->findById($stageId) ?? $updated;
    }
}
