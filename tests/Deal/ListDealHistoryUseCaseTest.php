<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Deal\ListDealHistoryUseCase;
use NeneDeal\Deal\StageHistoryEntry;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use PHPUnit\Framework\TestCase;

final class ListDealHistoryUseCaseTest extends TestCase
{
    private const DEAL_ID = '01DEALAAAAAAAAAAAAAAAAAAAAA';

    private InMemoryDealRepository $deals;
    private ListDealHistoryUseCase $useCase;

    protected function setUp(): void
    {
        $this->deals = new InMemoryDealRepository();
        $this->useCase = new ListDealHistoryUseCase($this->deals);
    }

    public function test_returns_history_newest_first(): void
    {
        $this->deals->save(new Deal(self::DEAL_ID, 'Acme', 1000, '01STAGEAA000000000000000AA', 10));
        // Append in chronological order; expect reversed
        $this->deals->appendHistory(new StageHistoryEntry('01HIST1000000000000000000A', self::DEAL_ID, null, '01STAGEAA000000000000000AA'));
        $this->deals->appendHistory(new StageHistoryEntry('01HIST2000000000000000000A', self::DEAL_ID, '01STAGEAA000000000000000AA', '01STAGEBB000000000000000AA'));

        $history = $this->useCase->execute(self::DEAL_ID);

        self::assertCount(2, $history);
        // Newest entry first: the second move
        self::assertSame('01STAGEAA000000000000000AA', $history[0]->fromStageId);
        self::assertSame('01STAGEBB000000000000000AA', $history[0]->toStageId);
        // Oldest entry last: the creation
        self::assertNull($history[1]->fromStageId);
    }

    public function test_returns_empty_list_for_deal_with_no_history(): void
    {
        $this->deals->save(new Deal(self::DEAL_ID, 'Acme', 1000, '01STAGEAA000000000000000AA', 10));

        $history = $this->useCase->execute(self::DEAL_ID);

        self::assertSame([], $history);
    }

    public function test_throws_for_unknown_deal(): void
    {
        $this->expectException(DealNotFoundException::class);
        $this->useCase->execute('01MISSINGDEAL000000000000AA');
    }
}
