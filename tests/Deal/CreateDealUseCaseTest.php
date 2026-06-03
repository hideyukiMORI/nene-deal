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

        $history = $this->deals->findActivity($deal->id);
        self::assertCount(1, $history);
        self::assertNull($history[0]->fromStageId);
        self::assertSame('01STAGELEAD0000000000000AA', $history[0]->toStageId);
    }

    public function test_resolves_stage_by_ulid(): void
    {
        $deal = $this->useCase->execute(new CreateDealInput(
            accountLabel: 'Acme',
            amountCents: 1000,
            stageRef: '01STAGELEAD0000000000000AA',
        ));

        self::assertSame('01STAGELEAD0000000000000AA', $deal->stageId);
    }

    public function test_all_optional_fields_are_preserved(): void
    {
        $deal = $this->useCase->execute(new CreateDealInput(
            accountLabel: 'Globex',
            amountCents: 500,
            stageRef: 'lead',
            probabilityPercent: 75,
            expectedCloseDate: '2026-12-31',
            ownerUserId: '01OWNER000000000000000000A',
            note: 'Important client',
        ));

        self::assertSame('2026-12-31', $deal->expectedCloseDate);
        self::assertSame('01OWNER000000000000000000A', $deal->ownerUserId);
        self::assertSame('Important client', $deal->note);
    }

    #[\PHPUnit\Framework\Attributes\TestWith([0])]
    #[\PHPUnit\Framework\Attributes\TestWith([100])]
    public function test_probability_boundary_values_are_stored(int $probability): void
    {
        $deal = $this->useCase->execute(new CreateDealInput(
            accountLabel: 'Boundary',
            amountCents: 1000,
            stageRef: 'lead',
            probabilityPercent: $probability,
        ));

        self::assertSame($probability, $deal->probabilityPercent);
    }

    public function test_amount_zero_is_valid(): void
    {
        $deal = $this->useCase->execute(new CreateDealInput(
            accountLabel: 'Zero',
            amountCents: 0,
            stageRef: 'lead',
        ));

        self::assertSame(0, $deal->amountCents);
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
