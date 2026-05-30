<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

/**
 * Outcome of a completed handoff (see the InvoiceHandoffResult schema in
 * docs/openapi/openapi.yaml).
 */
final readonly class InvoiceHandoffResult
{
    public function __construct(
        public string $dealId,
        public int $invoiceClientId,
        public int $invoiceQuoteId,
        public string $handoffAt,
        public ?string $handoffActorUserId = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'deal_id' => $this->dealId,
            'invoice_client_id' => $this->invoiceClientId,
            'invoice_quote_id' => $this->invoiceQuoteId,
            'handoff_at' => $this->handoffAt,
            'handoff_actor_user_id' => $this->handoffActorUserId,
        ];
    }
}
