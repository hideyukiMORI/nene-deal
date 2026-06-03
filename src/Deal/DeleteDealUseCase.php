<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Symfony\Component\Uid\Ulid;

final readonly class DeleteDealUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
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
    }
}
