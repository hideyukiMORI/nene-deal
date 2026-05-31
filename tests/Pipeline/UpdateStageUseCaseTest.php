<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Pipeline;

use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Pipeline\StageNotFoundException;
use NeneDeal\Pipeline\UpdateStageInput;
use NeneDeal\Pipeline\UpdateStageUseCase;
use NeneDeal\Tests\Support\InMemoryPipelineStageRepository;
use PHPUnit\Framework\TestCase;

final class UpdateStageUseCaseTest extends TestCase
{
    private const STAGE_ID = '01STAGELEAD0000000000000AA';
    private const ORG_ID = '01ORG00000000000000000000A';

    private InMemoryPipelineStageRepository $stages;
    private UpdateStageUseCase $useCase;

    protected function setUp(): void
    {
        $this->stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::STAGE_ID, self::ORG_ID, 'lead', 'Lead', 1, false, false),
        ]);
        $this->useCase = new UpdateStageUseCase($this->stages);
    }

    public function test_updates_label_only(): void
    {
        $updated = $this->useCase->execute(self::STAGE_ID, new UpdateStageInput(label: 'New Lead'));

        self::assertSame('New Lead', $updated->label);
        self::assertSame(1, $updated->sortOrder);
    }

    public function test_updates_sort_order_only(): void
    {
        $updated = $this->useCase->execute(self::STAGE_ID, new UpdateStageInput(sortOrder: 10));

        self::assertSame('Lead', $updated->label);
        self::assertSame(10, $updated->sortOrder);
    }

    public function test_updates_label_and_sort_order_together(): void
    {
        $updated = $this->useCase->execute(self::STAGE_ID, new UpdateStageInput(label: 'Renamed', sortOrder: 99));

        self::assertSame('Renamed', $updated->label);
        self::assertSame(99, $updated->sortOrder);
    }

    public function test_immutable_fields_are_not_modified(): void
    {
        $updated = $this->useCase->execute(self::STAGE_ID, new UpdateStageInput(label: 'X'));

        self::assertSame('lead', $updated->slug);
        self::assertFalse($updated->isTerminal);
        self::assertFalse($updated->isWon);
        self::assertSame(self::ORG_ID, $updated->organizationId);
    }

    public function test_sort_order_zero_is_valid(): void
    {
        $updated = $this->useCase->execute(self::STAGE_ID, new UpdateStageInput(sortOrder: 0));

        self::assertSame(0, $updated->sortOrder);
    }

    public function test_throws_for_unknown_stage(): void
    {
        $this->expectException(StageNotFoundException::class);
        $this->useCase->execute('01UNKNOWNSTAGE0000000000AA', new UpdateStageInput(label: 'X'));
    }
}
