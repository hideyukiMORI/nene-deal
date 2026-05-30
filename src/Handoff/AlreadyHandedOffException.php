<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use RuntimeException;

/**
 * Raised when a deal is already linked to Invoice. Carries the existing link
 * ids so the 409 response can guide the operator. Repeat handoff is idempotent:
 * no second client/quote is ever created.
 */
final class AlreadyHandedOffException extends RuntimeException
{
    public function __construct(
        public readonly string $dealId,
        public readonly int $invoiceClientId,
        public readonly int $invoiceQuoteId,
    ) {
        parent::__construct("Deal {$dealId} is already linked to an Invoice quote.");
    }
}
