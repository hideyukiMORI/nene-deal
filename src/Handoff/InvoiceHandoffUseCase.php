<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Deal\DealRepositoryInterface;
use NeneDeal\Pipeline\PipelineStageRepositoryInterface;

/**
 * Orchestrates the operator-confirmed won-deal handoff to NeNe Invoice:
 * creates a draft client and quote over HTTP, then persists the link ids.
 *
 * Idempotent: an already-linked deal raises {@see AlreadyHandedOffException}
 * (no second client/quote is created). Upstream failures leave the deal won but
 * unlinked — link ids are persisted only after both calls succeed.
 */
final readonly class InvoiceHandoffUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
        private PipelineStageRepositoryInterface $stages,
        private InvoiceClient $invoice,
    ) {
    }

    /**
     * @throws DealNotFoundException
     * @throws AlreadyHandedOffException
     * @throws HandoffPreconditionException
     * @throws InvoiceHandoffException
     */
    public function execute(string $dealId, ?string $actorUserId = null): InvoiceHandoffResult
    {
        $deal = $this->deals->findById($dealId);

        if ($deal === null) {
            throw new DealNotFoundException($dealId);
        }

        if ($deal->invoiceQuoteId !== null) {
            throw new AlreadyHandedOffException($dealId, $deal->invoiceClientId ?? 0, $deal->invoiceQuoteId);
        }

        $stage = $this->stages->findByIdOrSlug($deal->stageId);

        if ($stage === null || !$stage->isWon) {
            throw new HandoffPreconditionException('Deal must be in the won stage before handoff.');
        }

        if (trim($deal->accountLabel) === '') {
            throw new HandoffPreconditionException('Deal must have an account label before handoff.');
        }

        if ($deal->amountCents <= 0) {
            throw new HandoffPreconditionException('Deal must have a positive amount before handoff.');
        }

        $clientId = $this->invoice->createDraftClient($deal->accountLabel);
        $quoteId = $this->invoice->createDraftQuote($clientId, $deal->amountCents, $deal->accountLabel);

        $handoffAt = date('Y-m-d H:i:s');
        $this->deals->markHandedOff($dealId, $clientId, $quoteId, $handoffAt);

        return new InvoiceHandoffResult($dealId, $clientId, $quoteId, $handoffAt, $actorUserId);
    }
}
