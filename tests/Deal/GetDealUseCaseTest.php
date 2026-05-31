<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Deal\GetDealUseCase;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use PHPUnit\Framework\TestCase;

final class GetDealUseCaseTest extends TestCase
{
    private InMemoryDealRepository $deals;
    private GetDealUseCase $useCase;

    protected function setUp(): void
    {
        $this->deals = new InMemoryDealRepository();
        $this->useCase = new GetDealUseCase($this->deals);
    }

    public function test_returns_existing_deal(): void
    {
        $this->deals->save(new Deal('01DEALAAAAAAAAAAAAAAAAAAAAA', 'Acme', 1000, '01STAGE000000000000000000A', 50));

        $deal = $this->useCase->execute('01DEALAAAAAAAAAAAAAAAAAAAAA');

        self::assertSame('01DEALAAAAAAAAAAAAAAAAAAAAA', $deal->id);
        self::assertSame('Acme', $deal->accountLabel);
    }

    public function test_throws_for_unknown_id(): void
    {
        $this->expectException(DealNotFoundException::class);
        $this->useCase->execute('01MISSINGDEAL000000000000AA');
    }
}
