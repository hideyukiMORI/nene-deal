<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * Partial update for a deal. Non-nullable fields use null to mean "unchanged".
 * Nullable fields carry an explicit `has*` flag so that "set to null" (clear)
 * is distinguishable from "absent" (unchanged). Stage and Invoice link ids are
 * not updated here.
 */
final readonly class UpdateDealInput
{
    public function __construct(
        public ?string $accountLabel = null,
        public ?int $amountCents = null,
        public ?int $probabilityPercent = null,
        public bool $hasExpectedCloseDate = false,
        public ?string $expectedCloseDate = null,
        public bool $hasOwnerUserId = false,
        public ?string $ownerUserId = null,
        public bool $hasNote = false,
        public ?string $note = null,
    ) {
    }
}
