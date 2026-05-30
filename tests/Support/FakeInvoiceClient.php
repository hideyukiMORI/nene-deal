<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use NeneDeal\Handoff\InvoiceClient;
use NeneDeal\Handoff\InvoiceHandoffException;

/**
 * Test double for {@see InvoiceClient}. Returns preset ids and records calls,
 * or simulates an upstream failure when `$failOnQuote` / `$failOnClient` is set.
 */
final class FakeInvoiceClient implements InvoiceClient
{
    public int $clientCalls = 0;
    public int $quoteCalls = 0;

    public function __construct(
        private readonly int $clientId = 4821,
        private readonly int $quoteId = 9930,
        private readonly bool $failOnClient = false,
        private readonly bool $failOnQuote = false,
    ) {
    }

    public function createDraftClient(string $accountLabel): int
    {
        $this->clientCalls++;

        if ($this->failOnClient) {
            throw new InvoiceHandoffException('simulated client failure');
        }

        return $this->clientId;
    }

    public function createDraftQuote(int $clientId, int $amountCents, string $accountLabel): int
    {
        $this->quoteCalls++;

        if ($this->failOnQuote) {
            throw new InvoiceHandoffException('simulated quote failure');
        }

        return $this->quoteId;
    }
}
