<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Deal\DeleteDealUseCase;
use NeneDeal\Deal\StageHistoryEntry;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use PHPUnit\Framework\TestCase;

final class DeleteDealUseCaseTest extends TestCase
{
    private InMemoryDealRepository $deals;
    private DeleteDealUseCase $useCase;

    protected function setUp(): void
    {
        $this->deals = new InMemoryDealRepository();
        $this->useCase = new DeleteDealUseCase($this->deals);
    }

    public function test_removes_deal_from_repository(): void
    {
        $this->deals->save(new Deal('01DEALAAAAAAAAAAAAAAAAAAAAA', 'Acme', 1000, '01STAGE000000000000000000A', 50));

        $this->useCase->execute('01DEALAAAAAAAAAAAAAAAAAAAAA');

        self::assertNull($this->deals->findById('01DEALAAAAAAAAAAAAAAAAAAAAA'));
    }

    public function test_cascades_stage_history_on_delete(): void
    {
        $id = '01DEALAAAAAAAAAAAAAAAAAAAAA';
        $this->deals->save(new Deal($id, 'Acme', 1000, '01STAGE000000000000000000A', 50));
        $this->deals->appendHistory(new StageHistoryEntry('01HISTENTRY0000000000000000', $id, null, '01STAGE000000000000000000A'));

        $this->useCase->execute($id);

        self::assertCount(0, $this->deals->findHistory($id));
    }

    public function test_throws_for_unknown_id(): void
    {
        $this->expectException(DealNotFoundException::class);
        $this->useCase->execute('01MISSINGDEAL000000000000AA');
    }
}
