<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

final readonly class ListDealHistoryUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
    ) {
    }

    /**
     * @return list<DealActivity>
     * @throws DealNotFoundException
     */
    public function execute(string $dealId): array
    {
        // Include soft-deleted deals so their trail (and the delete itself)
        // stays visible.
        if ($this->deals->findByIdIncludingDeleted($dealId) === null) {
            throw new DealNotFoundException($dealId);
        }

        return $this->deals->findActivity($dealId);
    }
}
