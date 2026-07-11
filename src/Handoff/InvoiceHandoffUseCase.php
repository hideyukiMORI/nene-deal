<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use Nene2\Http\ClockInterface;
use NeneDeal\Audit\AuditAction;
use NeneDeal\Deal\DealActivity;
use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Deal\DealRepositoryInterface;
use NeneDeal\Pipeline\PipelineStageRepositoryInterface;
use NeneDeal\Tenancy\CurrentOrganization;
use Symfony\Component\Uid\Ulid;

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
        private ClockInterface $clock,
        private AuditRecorderInterface $audit,
        private CurrentOrganization $organization,
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

        $handoffAt = $this->clock->now()->format('Y-m-d H:i:s');
        $this->deals->markHandedOff($dealId, $clientId, $quoteId, $handoffAt);

        $this->deals->recordActivity(new DealActivity(
            id: (string) new Ulid(),
            dealId: $dealId,
            action: 'handoff',
            actorUserId: $actorUserId,
            changes: ['invoice_client_id' => ['from' => null, 'to' => $clientId], 'invoice_quote_id' => ['from' => null, 'to' => $quoteId]],
        ));

        $this->audit->record(new AuditEvent(
            action: AuditAction::DEAL_INVOICE_HANDOFF,
            entityType: 'deal',
            entityId: $dealId,
            actorId: $actorUserId,
            organizationId: $this->organization->id(),
            after: ['invoice_client_id' => $clientId, 'invoice_quote_id' => $quoteId],
        ));

        return new InvoiceHandoffResult($dealId, $clientId, $quoteId, $handoffAt, $actorUserId);
    }
}
