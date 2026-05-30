<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Forecast;

use NeneDeal\Deal\Deal;
use NeneDeal\Forecast\ComputeForecastUseCase;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use NeneDeal\Tests\Support\InMemoryPipelineStageRepository;
use PHPUnit\Framework\TestCase;

final class ComputeForecastUseCaseTest extends TestCase
{
    private const LEAD = '01STAGELEAD0000000000000AA';
    private const WON = '01STAGEWON00000000000000AA';

    public function test_aggregates_open_in_month_deals_excluding_terminal_and_out_of_range(): void
    {
        $deals = new InMemoryDealRepository();
        // In-month, open -> counted.
        $deals->save(new Deal('01DEALA0000000000000000000', 'Acme', 100_000, self::LEAD, 50, expectedCloseDate: '2026-06-10'));
        // In-month, terminal (won) -> excluded.
        $deals->save(new Deal('01DEALB0000000000000000000', 'Closed', 900_000, self::WON, 100, expectedCloseDate: '2026-06-20'));
        // Open but out of month -> excluded.
        $deals->save(new Deal('01DEALC0000000000000000000', 'Later', 300_000, self::LEAD, 80, expectedCloseDate: '2026-07-01'));

        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
            new PipelineStage(self::WON, 'org', 'won', 'Won', 5, true, true),
        ]);

        $summary = (new ComputeForecastUseCase($stages, $deals))->execute('2026-06');

        self::assertSame('2026-06', $summary->month);
        self::assertSame(1, $summary->openDealCount);
        self::assertSame(100_000, $summary->pipelineTotalCents);
        self::assertSame(50_000, $summary->weightedTotalCents);

        self::assertCount(1, $summary->byStage);
        self::assertSame('lead', $summary->byStage[0]->slug);
        self::assertSame(1, $summary->byStage[0]->dealCount);
        self::assertSame(50_000, $summary->byStage[0]->weightedTotalCents);
    }
}
