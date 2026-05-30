<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * Filters for listing deals. `query` matches account_label (case-insensitive
 * substring). Terminal-stage deals (won/lost) are excluded unless
 * `includeTerminal` is true.
 */
final readonly class DealFilter
{
    public function __construct(
        public ?string $stageRef = null,
        public ?string $ownerUserId = null,
        public ?string $query = null,
        public bool $includeTerminal = false,
    ) {
    }
}
