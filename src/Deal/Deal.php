<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * One sales opportunity (a card on the board), scoped to one organization.
 *
 * `amountCents` is JPY minor units (no floats). Invoice link ids are set only
 * after the won-deal handoff and are read-only via the API. `stageSlug` is a
 * read-only convenience populated from the joined stage on reads.
 */
final readonly class Deal
{
    public function __construct(
        public string $id,
        public string $accountLabel,
        public int $amountCents,
        public string $stageId,
        public int $probabilityPercent,
        public ?string $expectedCloseDate = null,
        public ?string $ownerUserId = null,
        public ?string $note = null,
        public ?int $invoiceClientId = null,
        public ?int $invoiceQuoteId = null,
        public ?string $handoffAt = null,
        public string $organizationId = '',
        public ?string $stageSlug = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}
