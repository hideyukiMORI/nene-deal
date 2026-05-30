<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

final readonly class DeleteDealUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
    ) {
    }

    /** @throws DealNotFoundException */
    public function execute(string $id): void
    {
        $this->deals->delete($id);
    }
}
