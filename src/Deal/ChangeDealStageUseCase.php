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

final readonly class ChangeDealStageUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
        private PipelineStageRepositoryInterface $stages,
        private AuditRecorderInterface $audit,
        private CurrentOrganization $organization,
    ) {
    }

    /**
     * Moves a deal to another stage, recording a history entry on change.
     * Moving into a won stage does not trigger the Invoice handoff (separate action).
     *
     * @throws DealNotFoundException
     * @throws UnknownStageException
     */
    public function execute(string $dealId, string $toStageRef, ?string $actorUserId = null): Deal
    {
        $deal = $this->deals->findById($dealId);

        if ($deal === null) {
            throw new DealNotFoundException($dealId);
        }

        $stage = $this->stages->findByIdOrSlug($toStageRef);

        if ($stage === null) {
            throw new UnknownStageException($toStageRef);
        }

        if ($stage->id !== $deal->stageId) {
            $fromStageId = $deal->stageId;

            $this->deals->update(new Deal(
                id: $deal->id,
                accountLabel: $deal->accountLabel,
                amountCents: $deal->amountCents,
                stageId: $stage->id,
                probabilityPercent: $deal->probabilityPercent,
                expectedCloseDate: $deal->expectedCloseDate,
                ownerUserId: $deal->ownerUserId,
                note: $deal->note,
                invoiceClientId: $deal->invoiceClientId,
                invoiceQuoteId: $deal->invoiceQuoteId,
                handoffAt: $deal->handoffAt,
            ));

            $this->deals->recordActivity(new DealActivity(
                id: (string) new Ulid(),
                dealId: $deal->id,
                action: 'stage_changed',
                fromStageId: $fromStageId,
                toStageId: $stage->id,
                actorUserId: $actorUserId,
            ));

            $this->audit->record(new AuditEvent(
                action: AuditAction::DEAL_STAGE_CHANGED,
                entityType: 'deal',
                entityId: $deal->id,
                actorId: $actorUserId,
                organizationId: $this->organization->id(),
                before: ['stage_id' => $fromStageId],
                after: ['stage_id' => $stage->id],
            ));
        }

        $updated = $this->deals->findById($dealId);

        if ($updated === null) {
            throw new LogicException('Deal could not be loaded immediately after stage change.');
        }

        return $updated;
    }
}
