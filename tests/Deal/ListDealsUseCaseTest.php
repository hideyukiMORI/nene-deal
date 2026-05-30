<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealFilter;
use NeneDeal\Deal\ListDealsUseCase;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use PHPUnit\Framework\TestCase;

final class ListDealsUseCaseTest extends TestCase
{
    public function test_pagination_reports_has_more_and_trims_to_limit(): void
    {
        $deals = new InMemoryDealRepository();
        for ($i = 1; $i <= 3; $i++) {
            $deals->save(new Deal(
                id: sprintf('01DEAL%020dA', $i),
                accountLabel: 'Deal ' . $i,
                amountCents: 1000 * $i,
                stageId: '01STAGELEAD0000000000000AA',
                probabilityPercent: 10,
            ));
        }

        $useCase = new ListDealsUseCase($deals);

        $first = $useCase->execute(new DealFilter(), 2, 0);
        self::assertCount(2, $first->items);
        self::assertTrue($first->hasMore);

        $second = $useCase->execute(new DealFilter(), 2, 2);
        self::assertCount(1, $second->items);
        self::assertFalse($second->hasMore);
    }

    public function test_query_filter_matches_account_label(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal('01DEALAAAAAAAAAAAAAAAAAAAAA', 'Acme Corp', 1000, '01STAGELEAD0000000000000AA', 10));
        $deals->save(new Deal('01DEALBBBBBBBBBBBBBBBBBBBBB', 'Globex', 2000, '01STAGELEAD0000000000000AA', 10));

        $useCase = new ListDealsUseCase($deals);
        $page = $useCase->execute(new DealFilter(query: 'acme'), 50, 0);

        self::assertCount(1, $page->items);
        self::assertSame('Acme Corp', $page->items[0]->accountLabel);
    }
}
