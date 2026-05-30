<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use NeneDeal\Deal\CreateDealInput;
use NeneDeal\Deal\CreateDealUseCase;
use NeneDeal\Deal\UnknownStageException;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use NeneDeal\Tests\Support\InMemoryPipelineStageRepository;
use PHPUnit\Framework\TestCase;

final class CreateDealUseCaseTest extends TestCase
{
    private InMemoryDealRepository $deals;
    private CreateDealUseCase $useCase;

    protected function setUp(): void
    {
        $this->deals = new InMemoryDealRepository();
        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage('01STAGELEAD0000000000000AA', 'org', 'lead', 'Lead', 1, false, false),
            new PipelineStage('01STAGEWON00000000000000AA', 'org', 'won', 'Won', 5, true, true),
        ]);
        $this->useCase = new CreateDealUseCase($this->deals, $stages);
    }

    public function test_creates_deal_resolving_stage_slug_and_records_history(): void
    {
        $deal = $this->useCase->execute(new CreateDealInput(
            accountLabel: 'Acme Corp',
            amountCents: 150_000_00,
            stageRef: 'lead',
            probabilityPercent: 30,
        ));

        self::assertSame('Acme Corp', $deal->accountLabel);
        self::assertSame(150_000_00, $deal->amountCents);
        self::assertSame('01STAGELEAD0000000000000AA', $deal->stageId);
        self::assertSame(30, $deal->probabilityPercent);

        $history = $this->deals->findHistory($deal->id);
        self::assertCount(1, $history);
        self::assertNull($history[0]->fromStageId);
        self::assertSame('01STAGELEAD0000000000000AA', $history[0]->toStageId);
    }

    public function test_rejects_unknown_stage(): void
    {
        $this->expectException(UnknownStageException::class);

        $this->useCase->execute(new CreateDealInput(
            accountLabel: 'Acme Corp',
            amountCents: 1000,
            stageRef: 'does-not-exist',
        ));
    }
}
