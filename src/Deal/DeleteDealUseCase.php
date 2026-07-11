<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use NeneDeal\Audit\AuditAction;
use NeneDeal\Tenancy\CurrentOrganization;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteDealUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
        private AuditRecorderInterface $audit,
        private CurrentOrganization $organization,
    ) {
    }

    /** Soft-deletes the deal (recoverable) and records the action. @throws DealNotFoundException */
    public function execute(string $id, ?string $actorUserId = null): void
    {
        $this->deals->delete($id, $actorUserId);

        $this->deals->recordActivity(new DealActivity(
            id: (string) new Ulid(),
            dealId: $id,
            action: 'deleted',
            actorUserId: $actorUserId,
        ));

        $this->audit->record(new AuditEvent(
            action: AuditAction::DEAL_DELETED,
            entityType: 'deal',
            entityId: $id,
            actorId: $actorUserId,
            organizationId: $this->organization->id(),
        ));
    }
}
