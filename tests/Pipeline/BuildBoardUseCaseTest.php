<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Pipeline;

use NeneDeal\Deal\Deal;
use NeneDeal\Pipeline\BuildBoardUseCase;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use NeneDeal\Tests\Support\InMemoryPipelineStageRepository;
use PHPUnit\Framework\TestCase;

final class BuildBoardUseCaseTest extends TestCase
{
    private const LEAD = '01STAGELEAD0000000000000AA';
    private const WON = '01STAGEWON00000000000000AA';

    private BuildBoardUseCase $useCase;

    protected function setUp(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal('01DEALA0000000000000000000', 'Acme', 100_000, self::LEAD, 50));
        $deals->save(new Deal('01DEALB0000000000000000000', 'Globex', 200_000, self::LEAD, 25));
        $deals->save(new Deal('01DEALC0000000000000000000', 'Closed', 500_000, self::WON, 100));

        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
            new PipelineStage(self::WON, 'org', 'won', 'Won', 5, true, true),
        ]);

        $this->useCase = new BuildBoardUseCase($stages, $deals);
    }

    public function test_excludes_terminal_columns_and_totals_per_column(): void
    {
        $board = $this->useCase->execute(null, false);

        self::assertCount(1, $board->columns);
        $lead = $board->columns[0];
        self::assertSame('lead', $lead->stage->slug);
        self::assertSame(2, $lead->dealCount);
        self::assertSame(300_000, $lead->totalCents);
        // weighted: 100000*50/100 + 200000*25/100 = 50000 + 50000
        self::assertSame(100_000, $lead->weightedTotalCents);
    }

    public function test_includes_terminal_columns_when_requested(): void
    {
        $board = $this->useCase->execute(null, true);

        self::assertCount(2, $board->columns);
        self::assertSame('won', $board->columns[1]->stage->slug);
        self::assertSame(1, $board->columns[1]->dealCount);
        self::assertSame(500_000, $board->columns[1]->weightedTotalCents);
    }
}
