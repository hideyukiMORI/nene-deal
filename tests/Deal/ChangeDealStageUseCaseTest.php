<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use NeneDeal\Deal\ChangeDealStageUseCase;
use NeneDeal\Deal\CreateDealInput;
use NeneDeal\Deal\CreateDealUseCase;
use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use NeneDeal\Tests\Support\InMemoryPipelineStageRepository;
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
        $create = new CreateDealUseCase($this->deals, $this->stages);
        $deal = $create->execute(new CreateDealInput(accountLabel: 'Acme', amountCents: 1000, stageRef: 'lead'));

        $useCase = new ChangeDealStageUseCase($this->deals, $this->stages);
        $moved = $useCase->execute($deal->id, 'proposal');

        self::assertSame('01STAGEPROP0000000000000AA', $moved->stageId);

        $history = $this->deals->findHistory($deal->id);
        self::assertCount(2, $history);
        // newest first: the move from lead -> proposal
        self::assertSame('01STAGELEAD0000000000000AA', $history[0]->fromStageId);
        self::assertSame('01STAGEPROP0000000000000AA', $history[0]->toStageId);
    }

    public function test_unknown_deal_throws(): void
    {
        $useCase = new ChangeDealStageUseCase($this->deals, $this->stages);

        $this->expectException(DealNotFoundException::class);
        $useCase->execute('01MISSINGDEAL000000000000A', 'proposal');
    }
}
