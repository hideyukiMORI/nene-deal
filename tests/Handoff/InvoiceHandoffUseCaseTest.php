<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Handoff;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Handoff\AlreadyHandedOffException;
use NeneDeal\Handoff\HandoffPreconditionException;
use NeneDeal\Handoff\InvoiceHandoffException;
use NeneDeal\Handoff\InvoiceHandoffUseCase;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Tests\Support\FakeInvoiceClient;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use NeneDeal\Tests\Support\InMemoryPipelineStageRepository;
use PHPUnit\Framework\TestCase;

final class InvoiceHandoffUseCaseTest extends TestCase
{
    private const LEAD = '01STAGELEAD0000000000000AA';
    private const WON = '01STAGEWON00000000000000AA';

    private InMemoryDealRepository $deals;
    private InMemoryPipelineStageRepository $stages;

    protected function setUp(): void
    {
        $this->deals = new InMemoryDealRepository();
        $this->stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
            new PipelineStage(self::WON, 'org', 'won', 'Won', 5, true, true),
        ]);
    }

    private function wonDeal(string $id = '01DEALWON00000000000000000'): Deal
    {
        return new Deal($id, 'Acme Corp', 150_000_00, self::WON, 100);
    }

    public function test_creates_draft_client_and_quote_and_persists_link_ids(): void
    {
        $this->deals->save($this->wonDeal());
        $invoice = new FakeInvoiceClient(4821, 9930);

        $result = (new InvoiceHandoffUseCase($this->deals, $this->stages, $invoice))->execute('01DEALWON00000000000000000');

        self::assertSame(4821, $result->invoiceClientId);
        self::assertSame(9930, $result->invoiceQuoteId);

        $deal = $this->deals->findById('01DEALWON00000000000000000');
        self::assertNotNull($deal);
        self::assertSame(4821, $deal->invoiceClientId);
        self::assertSame(9930, $deal->invoiceQuoteId);
        self::assertNotNull($deal->handoffAt);
    }

    public function test_already_linked_is_idempotent_and_rejected_with_existing_ids(): void
    {
        $this->deals->save(new Deal('01DEALWON00000000000000000', 'Acme', 1000, self::WON, 100, invoiceClientId: 11, invoiceQuoteId: 22));
        $invoice = new FakeInvoiceClient();

        try {
            (new InvoiceHandoffUseCase($this->deals, $this->stages, $invoice))->execute('01DEALWON00000000000000000');
            self::fail('Expected AlreadyHandedOffException.');
        } catch (AlreadyHandedOffException $e) {
            self::assertSame(11, $e->invoiceClientId);
            self::assertSame(22, $e->invoiceQuoteId);
        }

        // No second client/quote created.
        self::assertSame(0, $invoice->clientCalls);
        self::assertSame(0, $invoice->quoteCalls);
    }

    public function test_non_won_stage_is_rejected_before_calling_invoice(): void
    {
        $this->deals->save(new Deal('01DEALLEAD0000000000000000', 'Acme', 1000, self::LEAD, 50));
        $invoice = new FakeInvoiceClient();

        try {
            (new InvoiceHandoffUseCase($this->deals, $this->stages, $invoice))->execute('01DEALLEAD0000000000000000');
            self::fail('Expected HandoffPreconditionException.');
        } catch (HandoffPreconditionException) {
            self::assertSame(0, $invoice->clientCalls);
        }
    }

    public function test_unknown_deal_throws(): void
    {
        $this->expectException(DealNotFoundException::class);
        (new InvoiceHandoffUseCase($this->deals, $this->stages, new FakeInvoiceClient()))->execute('01MISSING000000000000000AA');
    }

    public function test_upstream_quote_failure_leaves_deal_unlinked(): void
    {
        $this->deals->save($this->wonDeal());
        $invoice = new FakeInvoiceClient(failOnQuote: true);

        try {
            (new InvoiceHandoffUseCase($this->deals, $this->stages, $invoice))->execute('01DEALWON00000000000000000');
            self::fail('Expected InvoiceHandoffException.');
        } catch (InvoiceHandoffException) {
            $deal = $this->deals->findById('01DEALWON00000000000000000');
            self::assertNotNull($deal);
            self::assertNull($deal->invoiceClientId);
            self::assertNull($deal->invoiceQuoteId);
        }
    }

    public function test_upstream_client_failure_leaves_deal_unlinked_and_quote_not_called(): void
    {
        $this->deals->save($this->wonDeal());
        $invoice = new FakeInvoiceClient(failOnClient: true);

        try {
            (new InvoiceHandoffUseCase($this->deals, $this->stages, $invoice))->execute('01DEALWON00000000000000000');
            self::fail('Expected InvoiceHandoffException.');
        } catch (InvoiceHandoffException) {
            self::assertSame(1, $invoice->clientCalls);
            self::assertSame(0, $invoice->quoteCalls);

            $deal = $this->deals->findById('01DEALWON00000000000000000');
            self::assertNotNull($deal);
            self::assertNull($deal->invoiceClientId);
        }
    }

    public function test_empty_account_label_is_precondition_failure(): void
    {
        $this->deals->save(new Deal('01DEALWON00000000000000000', '', 150_000_00, self::WON, 100));
        $invoice = new FakeInvoiceClient();

        $this->expectException(HandoffPreconditionException::class);
        (new InvoiceHandoffUseCase($this->deals, $this->stages, $invoice))->execute('01DEALWON00000000000000000');

        self::assertSame(0, $invoice->clientCalls);
    }

    public function test_whitespace_only_account_label_is_precondition_failure(): void
    {
        $this->deals->save(new Deal('01DEALWON00000000000000000', '   ', 150_000_00, self::WON, 100));
        $invoice = new FakeInvoiceClient();

        $this->expectException(HandoffPreconditionException::class);
        (new InvoiceHandoffUseCase($this->deals, $this->stages, $invoice))->execute('01DEALWON00000000000000000');
    }

    public function test_amount_zero_is_precondition_failure(): void
    {
        $this->deals->save(new Deal('01DEALWON00000000000000000', 'Acme Corp', 0, self::WON, 100));
        $invoice = new FakeInvoiceClient();

        $this->expectException(HandoffPreconditionException::class);
        (new InvoiceHandoffUseCase($this->deals, $this->stages, $invoice))->execute('01DEALWON00000000000000000');
    }

    public function test_actor_user_id_recorded_on_result(): void
    {
        $this->deals->save($this->wonDeal());

        $result = (new InvoiceHandoffUseCase($this->deals, $this->stages, new FakeInvoiceClient()))
            ->execute('01DEALWON00000000000000000', '01ACTOR00000000000000000AA');

        self::assertSame('01ACTOR00000000000000000AA', $result->handoffActorUserId);
    }
}
