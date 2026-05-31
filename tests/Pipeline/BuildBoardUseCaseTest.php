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

    public function test_owner_filter_returns_only_that_owners_deals(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal('01DEALA0000000000000000000', 'Alice deal', 100_000, self::LEAD, 50, ownerUserId: 'alice'));
        $deals->save(new Deal('01DEALB0000000000000000000', 'Bob deal', 200_000, self::LEAD, 50, ownerUserId: 'bob'));

        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
        ]);

        $board = (new BuildBoardUseCase($stages, $deals))->execute('alice', false);

        self::assertCount(1, $board->columns);
        self::assertCount(1, $board->columns[0]->deals);
        self::assertSame('Alice deal', $board->columns[0]->deals[0]->accountLabel);
    }

    public function test_empty_stages_returns_empty_board(): void
    {
        $stages = new InMemoryPipelineStageRepository([]);
        $deals = new InMemoryDealRepository();

        $board = (new BuildBoardUseCase($stages, $deals))->execute(null, false);

        self::assertSame([], $board->columns);
    }

    public function test_column_order_matches_sort_order(): void
    {
        $stages = new InMemoryPipelineStageRepository([
            // Registered in reverse order to confirm sort_order takes precedence
            new PipelineStage('01STAGEPROP0000000000000AA', 'org', 'proposal', 'Proposal', 3, false, false),
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
        ]);
        $deals = new InMemoryDealRepository();

        $board = (new BuildBoardUseCase($stages, $deals))->execute(null, false);

        self::assertCount(2, $board->columns);
        self::assertSame('lead', $board->columns[0]->stage->slug);
        self::assertSame('proposal', $board->columns[1]->stage->slug);
    }

    public function test_weighted_total_rounds_down_on_fractional_cents(): void
    {
        $deals = new InMemoryDealRepository();
        // 100001 * 33 / 100 = 33000.33 -> truncated to 33000
        $deals->save(new Deal('01DEALA0000000000000000000', 'Rounding', 100_001, self::LEAD, 33));

        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
        ]);

        $board = (new BuildBoardUseCase($stages, $deals))->execute(null, false);

        self::assertSame(100_001, $board->columns[0]->totalCents);
        self::assertSame(33_000, $board->columns[0]->weightedTotalCents);
    }
}
