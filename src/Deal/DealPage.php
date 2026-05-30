<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * A page of deals plus whether a further page exists (drives next_cursor).
 */
final readonly class DealPage
{
    /** @param list<Deal> $items */
    public function __construct(
        public array $items,
        public bool $hasMore,
    ) {
    }
}
