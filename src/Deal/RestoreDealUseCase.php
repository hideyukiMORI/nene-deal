<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use LogicException;
use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use NeneDeal\Audit\AuditAction;
use NeneDeal\Tenancy\CurrentOrganization;
use Symfony\Component\Uid\Ulid;

final readonly class RestoreDealUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
        private AuditRecorderInterface $audit,
        private CurrentOrganization $organization,
    ) {
    }

    /** Restores a soft-deleted deal and records the action. @throws DealNotFoundException */
    public function execute(string $id, ?string $actorUserId = null): Deal
    {
        $this->deals->restore($id);

        $this->deals->recordActivity(new DealActivity(
            id: (string) new Ulid(),
            dealId: $id,
            action: 'restored',
            actorUserId: $actorUserId,
        ));

        $restored = $this->deals->findById($id);

        if ($restored === null) {
            throw new LogicException('Deal could not be loaded immediately after restore.');
        }

        $this->audit->record(new AuditEvent(
            action: AuditAction::DEAL_RESTORED,
            entityType: 'deal',
            entityId: $id,
            actorId: $actorUserId,
            organizationId: $this->organization->id(),
        ));

        return $restored;
    }
}
