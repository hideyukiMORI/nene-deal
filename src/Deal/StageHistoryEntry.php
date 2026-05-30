<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * An audit-lite record of a deal moving between stages.
 * `fromStageId` is null when the deal was created at its first stage.
 */
final readonly class StageHistoryEntry
{
    public function __construct(
        public string $id,
        public string $dealId,
        public ?string $fromStageId,
        public string $toStageId,
        public ?string $actorUserId = null,
        public ?string $createdAt = null,
    ) {
    }
}
