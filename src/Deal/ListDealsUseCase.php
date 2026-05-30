<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

final readonly class ListDealsUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
    ) {
    }

    public function execute(DealFilter $filter, int $limit, int $offset): DealPage
    {
        // Fetch one extra row to detect whether a further page exists.
        $rows = $this->deals->findAll($filter, $limit + 1, $offset);

        $hasMore = count($rows) > $limit;
        $items = $hasMore ? array_slice($rows, 0, $limit) : $rows;

        return new DealPage($items, $hasMore);
    }
}
