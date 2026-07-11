<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use LogicException;
use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use NeneDeal\Audit\AuditAction;
use NeneDeal\Pipeline\PipelineStageRepositoryInterface;
use NeneDeal\Tenancy\CurrentOrganization;
use Symfony\Component\Uid\Ulid;

final readonly class CreateDealUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
        private PipelineStageRepositoryInterface $stages,
        private AuditRecorderInterface $audit,
        private CurrentOrganization $organization,
    ) {
    }

    /** @throws UnknownStageException */
    public function execute(CreateDealInput $input, ?string $actorUserId = null): Deal
    {
        $stage = $this->stages->findByIdOrSlug($input->stageRef);

        if ($stage === null) {
            throw new UnknownStageException($input->stageRef);
        }

        $id = (string) new Ulid();

        // Default the owner to the creator so every deal has a visible owner.
        $ownerUserId = $input->ownerUserId ?? $actorUserId;

        $this->deals->save(new Deal(
            id: $id,
            accountLabel: $input->accountLabel,
            amountCents: $input->amountCents,
            stageId: $stage->id,
            probabilityPercent: $input->probabilityPercent,
            expectedCloseDate: $input->expectedCloseDate,
            ownerUserId: $ownerUserId,
            note: $input->note,
        ));

        $this->deals->recordActivity(new DealActivity(
            id: (string) new Ulid(),
            dealId: $id,
            action: 'created',
            fromStageId: null,
            toStageId: $stage->id,
            actorUserId: $actorUserId,
        ));

        $created = $this->deals->findById($id);

        if ($created === null) {
            throw new LogicException('Deal could not be loaded immediately after creation.');
        }

        $this->audit->record(new AuditEvent(
            action: AuditAction::DEAL_CREATED,
            entityType: 'deal',
            entityId: $id,
            actorId: $actorUserId,
            organizationId: $this->organization->id(),
            after: [
                'account_label' => $created->accountLabel,
                'amount_cents' => $created->amountCents,
                'stage_id' => $created->stageId,
                'probability_percent' => $created->probabilityPercent,
                'expected_close_date' => $created->expectedCloseDate,
                'owner_user_id' => $created->ownerUserId,
            ],
        ));

        return $created;
    }
}
