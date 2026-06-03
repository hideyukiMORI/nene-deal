<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Deal\DeleteDealUseCase;
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

    public function test_soft_deletes_so_the_deal_is_recoverable(): void
    {
        $id = '01DEALAAAAAAAAAAAAAAAAAAAAA';
        $this->deals->save(new Deal($id, 'Acme', 1000, '01STAGE000000000000000000A', 50));

        $this->useCase->execute($id);

        // Hidden from normal reads, but retained so it can be restored.
        self::assertNull($this->deals->findById($id));
        self::assertNotNull($this->deals->findByIdIncludingDeleted($id));
    }

    public function test_records_a_deleted_activity_entry_with_actor(): void
    {
        $id = '01DEALAAAAAAAAAAAAAAAAAAAAA';
        $this->deals->save(new Deal($id, 'Acme', 1000, '01STAGE000000000000000000A', 50));

        $this->useCase->execute($id, '01ACTOR00000000000000000AA');

        $activity = $this->deals->findActivity($id);
        self::assertCount(1, $activity);
        self::assertSame('deleted', $activity[0]->action);
        self::assertSame('01ACTOR00000000000000000AA', $activity[0]->actorUserId);
    }

    public function test_throws_for_unknown_id(): void
    {
        $this->expectException(DealNotFoundException::class);
        $this->useCase->execute('01MISSINGDEAL000000000000AA');
    }
}
