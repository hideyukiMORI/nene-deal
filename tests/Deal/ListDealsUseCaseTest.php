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

    public function test_stage_filter_returns_only_matching_stage(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal('01DEALAAAAAAAAAAAAAAAAAAAAA', 'Lead deal', 1000, '01STAGELEAD0000000000000AA', 10));
        $deals->save(new Deal('01DEALBBBBBBBBBBBBBBBBBBBBB', 'Prop deal', 2000, '01STAGEPROP0000000000000AA', 30));

        $useCase = new ListDealsUseCase($deals);
        $page = $useCase->execute(new DealFilter(stageRef: '01STAGELEAD0000000000000AA'), 50, 0);

        self::assertCount(1, $page->items);
        self::assertSame('Lead deal', $page->items[0]->accountLabel);
    }

    public function test_owner_filter_returns_only_matching_owner(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal('01DEALAAAAAAAAAAAAAAAAAAAAA', 'Alice deal', 1000, '01STAGE000000000000000000A', 10, ownerUserId: 'alice'));
        $deals->save(new Deal('01DEALBBBBBBBBBBBBBBBBBBBBB', 'Bob deal', 2000, '01STAGE000000000000000000A', 10, ownerUserId: 'bob'));

        $useCase = new ListDealsUseCase($deals);
        $page = $useCase->execute(new DealFilter(ownerUserId: 'alice'), 50, 0);

        self::assertCount(1, $page->items);
        self::assertSame('Alice deal', $page->items[0]->accountLabel);
    }

    public function test_empty_result_returns_no_items_and_has_more_false(): void
    {
        $deals = new InMemoryDealRepository();
        $useCase = new ListDealsUseCase($deals);

        $page = $useCase->execute(new DealFilter(), 50, 0);

        self::assertCount(0, $page->items);
        self::assertFalse($page->hasMore);
    }

    public function test_limit_one_on_multiple_deals(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal('01DEALAAAAAAAAAAAAAAAAAAAAA', 'A', 1000, '01STAGE000000000000000000A', 10));
        $deals->save(new Deal('01DEALBBBBBBBBBBBBBBBBBBBBB', 'B', 2000, '01STAGE000000000000000000A', 10));
        $deals->save(new Deal('01DEALCCCCCCCCCCCCCCCCCCCCC', 'C', 3000, '01STAGE000000000000000000A', 10));

        $useCase = new ListDealsUseCase($deals);
        $page = $useCase->execute(new DealFilter(), 1, 0);

        self::assertCount(1, $page->items);
        self::assertTrue($page->hasMore);
    }

    public function test_offset_past_last_item_returns_empty_without_has_more(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal('01DEALAAAAAAAAAAAAAAAAAAAAA', 'Only', 1000, '01STAGE000000000000000000A', 10));

        $useCase = new ListDealsUseCase($deals);
        $page = $useCase->execute(new DealFilter(), 50, 99);

        self::assertCount(0, $page->items);
        self::assertFalse($page->hasMore);
    }
}
