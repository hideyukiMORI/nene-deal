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
        $deals->save(new Deal('01DEALA0000000000000000000', 'Acme', 100_000, self::LEAD, 50, expectedCloseDate: '2026-06-10'));
        $deals->save(new Deal('01DEALB0000000000000000000', 'Closed', 900_000, self::WON, 100, expectedCloseDate: '2026-06-20'));
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

    public function test_deal_without_expected_close_date_is_excluded(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal('01DEALA0000000000000000000', 'No Date', 200_000, self::LEAD, 50));

        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
        ]);

        $summary = (new ComputeForecastUseCase($stages, $deals))->execute('2026-06');

        self::assertSame(0, $summary->openDealCount);
        self::assertSame(0, $summary->pipelineTotalCents);
        self::assertSame(0, $summary->weightedTotalCents);
    }

    public function test_probability_zero_contributes_to_pipeline_total_but_not_weighted(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal('01DEALA0000000000000000000', 'Cold', 100_000, self::LEAD, 0, expectedCloseDate: '2026-06-15'));

        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
        ]);

        $summary = (new ComputeForecastUseCase($stages, $deals))->execute('2026-06');

        self::assertSame(1, $summary->openDealCount);
        self::assertSame(100_000, $summary->pipelineTotalCents);
        self::assertSame(0, $summary->weightedTotalCents);
    }

    public function test_probability_100_weighted_equals_pipeline_total(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal('01DEALA0000000000000000000', 'Sure', 500_000, self::LEAD, 100, expectedCloseDate: '2026-06-01'));

        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
        ]);

        $summary = (new ComputeForecastUseCase($stages, $deals))->execute('2026-06');

        self::assertSame(500_000, $summary->pipelineTotalCents);
        self::assertSame(500_000, $summary->weightedTotalCents);
    }

    public function test_month_boundary_first_and_last_day_are_included(): void
    {
        $deals = new InMemoryDealRepository();
        // First day of month
        $deals->save(new Deal('01DEALA0000000000000000000', 'First', 100_000, self::LEAD, 100, expectedCloseDate: '2026-06-01'));
        // Last day of month
        $deals->save(new Deal('01DEALB0000000000000000000', 'Last', 100_000, self::LEAD, 100, expectedCloseDate: '2026-06-30'));
        // Day before month — excluded
        $deals->save(new Deal('01DEALC0000000000000000000', 'Before', 999_000, self::LEAD, 100, expectedCloseDate: '2026-05-31'));
        // Day after month — excluded
        $deals->save(new Deal('01DEALD0000000000000000000', 'After', 999_000, self::LEAD, 100, expectedCloseDate: '2026-07-01'));

        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
        ]);

        $summary = (new ComputeForecastUseCase($stages, $deals))->execute('2026-06');

        self::assertSame(2, $summary->openDealCount);
        self::assertSame(200_000, $summary->pipelineTotalCents);
    }

    public function test_empty_month_returns_zero_totals(): void
    {
        $deals = new InMemoryDealRepository();
        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
        ]);

        $summary = (new ComputeForecastUseCase($stages, $deals))->execute('2026-06');

        self::assertSame(0, $summary->openDealCount);
        self::assertSame(0, $summary->pipelineTotalCents);
        self::assertSame(0, $summary->weightedTotalCents);
        self::assertSame([], $summary->byStage);
    }

    public function test_multiple_stages_broken_down_separately(): void
    {
        $prop = '01STAGEPROP0000000000000AA';

        $deals = new InMemoryDealRepository();
        $deals->save(new Deal('01DEALA0000000000000000000', 'Lead deal', 100_000, self::LEAD, 20, expectedCloseDate: '2026-06-10'));
        $deals->save(new Deal('01DEALB0000000000000000000', 'Prop deal', 200_000, $prop, 80, expectedCloseDate: '2026-06-20'));

        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage(self::LEAD, 'org', 'lead', 'Lead', 1, false, false),
            new PipelineStage($prop, 'org', 'proposal', 'Proposal', 3, false, false),
        ]);

        $summary = (new ComputeForecastUseCase($stages, $deals))->execute('2026-06');

        self::assertSame(2, $summary->openDealCount);
        self::assertCount(2, $summary->byStage);

        $bySlug = array_column($summary->byStage, null, 'slug');
        // 100000 * 20 / 100 = 20000
        self::assertSame(20_000, $bySlug['lead']->weightedTotalCents);
        // 200000 * 80 / 100 = 160000
        self::assertSame(160_000, $bySlug['proposal']->weightedTotalCents);
    }
}
