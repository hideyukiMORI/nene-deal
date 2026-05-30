<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

final readonly class CreateDealInput
{
    public function __construct(
        public string $accountLabel,
        public int $amountCents,
        public string $stageRef,
        public int $probabilityPercent = 0,
        public ?string $expectedCloseDate = null,
        public ?string $ownerUserId = null,
        public ?string $note = null,
    ) {
    }
}
