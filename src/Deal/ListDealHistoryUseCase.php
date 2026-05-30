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
     * @return list<StageHistoryEntry>
     * @throws DealNotFoundException
     */
    public function execute(string $dealId): array
    {
        if ($this->deals->findById($dealId) === null) {
            throw new DealNotFoundException($dealId);
        }

        return $this->deals->findHistory($dealId);
    }
}
