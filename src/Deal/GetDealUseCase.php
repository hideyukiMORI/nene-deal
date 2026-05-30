<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

final readonly class GetDealUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
    ) {
    }

    /** @throws DealNotFoundException */
    public function execute(string $id): Deal
    {
        $deal = $this->deals->findById($id);

        if ($deal === null) {
            throw new DealNotFoundException($id);
        }

        return $deal;
    }
}
