<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

/**
 * Deal-side abstraction over the NeNe Invoice admin HTTP API. Deal creates
 * **draft** resources only and never writes payments or reconciliation records.
 *
 * Implementations must throw {@see InvoiceHandoffException} on any transport,
 * configuration, or non-2xx upstream condition so that the deal is left won but
 * unlinked (no partial link).
 */
interface InvoiceClient
{
    /**
     * Creates a draft client in NeNe Invoice from the deal's account label.
     *
     * @return int the Invoice client id
     * @throws InvoiceHandoffException
     */
    public function createDraftClient(string $accountLabel): int;

    /**
     * Creates a draft quote in NeNe Invoice for the given client and headline amount.
     *
     * @return int the Invoice quote id
     * @throws InvoiceHandoffException
     */
    public function createDraftQuote(int $clientId, int $amountCents, string $accountLabel): int;
}
