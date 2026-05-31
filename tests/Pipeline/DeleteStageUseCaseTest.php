<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Pipeline;

use NeneDeal\Pipeline\DeleteStageUseCase;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Pipeline\StageDeletionForbiddenException;
use NeneDeal\Pipeline\StageHasDealsException;
use NeneDeal\Pipeline\StageNotFoundException;
use NeneDeal\Tests\Support\InMemoryPipelineStageRepository;
use PHPUnit\Framework\TestCase;

final class DeleteStageUseCaseTest extends TestCase
{
    private const ORG_ID = '01ORG00000000000000000000A';

    private InMemoryPipelineStageRepository $stages;
    private DeleteStageUseCase $useCase;

    protected function setUp(): void
    {
        $this->stages = new InMemoryPipelineStageRepository([
            new PipelineStage('01STAGELEAD0000000000000AA', self::ORG_ID, 'lead', 'Lead', 1, false, false),
            new PipelineStage('01STAGEWON00000000000000AA', self::ORG_ID, 'won', 'Won', 5, true, true),
        ]);
        $this->useCase = new DeleteStageUseCase($this->stages);
    }

    public function test_deletes_empty_non_terminal_stage(): void
    {
        $this->useCase->execute('01STAGELEAD0000000000000AA');

        self::assertNull($this->stages->findById('01STAGELEAD0000000000000AA'));
    }

    public function test_throws_when_stage_is_terminal(): void
    {
        $this->expectException(StageDeletionForbiddenException::class);
        $this->useCase->execute('01STAGEWON00000000000000AA');
    }

    public function test_throws_when_stage_has_deals(): void
    {
        $this->stages->addDealToStage('01STAGELEAD0000000000000AA');

        $this->expectException(StageHasDealsException::class);
        $this->useCase->execute('01STAGELEAD0000000000000AA');
    }

    public function test_throws_when_stage_not_found(): void
    {
        $this->expectException(StageNotFoundException::class);
        $this->useCase->execute('01UNKNOWNSTAGE0000000000AA');
    }
}
