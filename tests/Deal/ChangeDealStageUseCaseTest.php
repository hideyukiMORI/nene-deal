<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use NeneDeal\Deal\ChangeDealStageUseCase;
use NeneDeal\Deal\CreateDealInput;
use NeneDeal\Deal\CreateDealUseCase;
use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Deal\UnknownStageException;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use NeneDeal\Tests\Support\InMemoryPipelineStageRepository;
use NeneDeal\Tests\Support\RecordingAuditRecorder;
use NeneDeal\Tests\Support\StubCurrentOrganization;
use PHPUnit\Framework\TestCase;

final class ChangeDealStageUseCaseTest extends TestCase
{
    private InMemoryDealRepository $deals;
    private InMemoryPipelineStageRepository $stages;

    protected function setUp(): void
    {
        $this->deals = new InMemoryDealRepository();
        $this->stages = new InMemoryPipelineStageRepository([
            new PipelineStage('01STAGELEAD0000000000000AA', 'org', 'lead', 'Lead', 1, false, false),
            new PipelineStage('01STAGEPROP0000000000000AA', 'org', 'proposal', 'Proposal', 3, false, false),
        ]);
    }

    public function test_moves_stage_and_records_history(): void
    {
        $create = new CreateDealUseCase($this->deals, $this->stages, new RecordingAuditRecorder(), new StubCurrentOrganization('01ORG00000000000000000000A'));
        $deal = $create->execute(new CreateDealInput(accountLabel: 'Acme', amountCents: 1000, stageRef: 'lead'));

        $useCase = new ChangeDealStageUseCase($this->deals, $this->stages, new RecordingAuditRecorder(), new StubCurrentOrganization('01ORG00000000000000000000A'));
        $moved = $useCase->execute($deal->id, 'proposal');

        self::assertSame('01STAGEPROP0000000000000AA', $moved->stageId);

        $history = $this->deals->findActivity($deal->id);
        self::assertCount(2, $history);
        // newest first: the move from lead -> proposal
        self::assertSame('01STAGELEAD0000000000000AA', $history[0]->fromStageId);
        self::assertSame('01STAGEPROP0000000000000AA', $history[0]->toStageId);
    }

    public function test_no_history_entry_when_moving_to_same_stage(): void
    {
        $create = new CreateDealUseCase($this->deals, $this->stages, new RecordingAuditRecorder(), new StubCurrentOrganization('01ORG00000000000000000000A'));
        $deal = $create->execute(new CreateDealInput(accountLabel: 'Acme', amountCents: 1000, stageRef: 'lead'));

        $useCase = new ChangeDealStageUseCase($this->deals, $this->stages, new RecordingAuditRecorder(), new StubCurrentOrganization('01ORG00000000000000000000A'));
        $unchanged = $useCase->execute($deal->id, 'lead');

        self::assertSame('01STAGELEAD0000000000000AA', $unchanged->stageId);
        // Only the initial creation entry; no move entry added
        self::assertCount(1, $this->deals->findActivity($deal->id));
    }

    public function test_actor_user_id_recorded_in_history(): void
    {
        $create = new CreateDealUseCase($this->deals, $this->stages, new RecordingAuditRecorder(), new StubCurrentOrganization('01ORG00000000000000000000A'));
        $deal = $create->execute(new CreateDealInput(accountLabel: 'Acme', amountCents: 1000, stageRef: 'lead'));

        $useCase = new ChangeDealStageUseCase($this->deals, $this->stages, new RecordingAuditRecorder(), new StubCurrentOrganization('01ORG00000000000000000000A'));
        $useCase->execute($deal->id, 'proposal', '01ACTOR00000000000000000AA');

        $history = $this->deals->findActivity($deal->id);
        self::assertSame('01ACTOR00000000000000000AA', $history[0]->actorUserId);
    }

    public function test_unknown_target_stage_throws(): void
    {
        $create = new CreateDealUseCase($this->deals, $this->stages, new RecordingAuditRecorder(), new StubCurrentOrganization('01ORG00000000000000000000A'));
        $deal = $create->execute(new CreateDealInput(accountLabel: 'Acme', amountCents: 1000, stageRef: 'lead'));

        $this->expectException(UnknownStageException::class);
        (new ChangeDealStageUseCase($this->deals, $this->stages, new RecordingAuditRecorder(), new StubCurrentOrganization('01ORG00000000000000000000A')))->execute($deal->id, 'does-not-exist');
    }

    public function test_move_to_terminal_stage_succeeds_without_auto_handoff(): void
    {
        $this->stages = new InMemoryPipelineStageRepository([
            new PipelineStage('01STAGELEAD0000000000000AA', 'org', 'lead', 'Lead', 1, false, false),
            new PipelineStage('01STAGEWON00000000000000AA', 'org', 'won', 'Won', 5, true, true),
        ]);

        $create = new CreateDealUseCase($this->deals, $this->stages, new RecordingAuditRecorder(), new StubCurrentOrganization('01ORG00000000000000000000A'));
        $deal = $create->execute(new CreateDealInput(accountLabel: 'Acme', amountCents: 1000, stageRef: 'lead'));

        $moved = (new ChangeDealStageUseCase($this->deals, $this->stages, new RecordingAuditRecorder(), new StubCurrentOrganization('01ORG00000000000000000000A')))->execute($deal->id, 'won');

        self::assertSame('01STAGEWON00000000000000AA', $moved->stageId);
        self::assertNull($moved->invoiceClientId);
        self::assertNull($moved->invoiceQuoteId);
    }

    public function test_unknown_deal_throws(): void
    {
        $useCase = new ChangeDealStageUseCase($this->deals, $this->stages, new RecordingAuditRecorder(), new StubCurrentOrganization('01ORG00000000000000000000A'));

        $this->expectException(DealNotFoundException::class);
        $useCase->execute('01MISSINGDEAL000000000000A', 'proposal');
    }
}
