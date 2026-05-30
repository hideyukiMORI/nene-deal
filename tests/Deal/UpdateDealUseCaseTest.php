<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Deal\UpdateDealInput;
use NeneDeal\Deal\UpdateDealUseCase;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use PHPUnit\Framework\TestCase;

final class UpdateDealUseCaseTest extends TestCase
{
    public function test_updates_only_provided_fields_and_can_clear_nullable(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal(
            id: '01DEALAAAAAAAAAAAAAAAAAAAAA',
            accountLabel: 'Acme',
            amountCents: 1000,
            stageId: '01STAGELEAD0000000000000AA',
            probabilityPercent: 10,
            note: 'keep or clear',
            ownerUserId: '01OWNERAAAAAAAAAAAAAAAAAAAA',
        ));

        $useCase = new UpdateDealUseCase($deals);

        // Change amount only; note untouched (absent), owner untouched.
        $updated = $useCase->execute('01DEALAAAAAAAAAAAAAAAAAAAAA', new UpdateDealInput(amountCents: 5000));
        self::assertSame(5000, $updated->amountCents);
        self::assertSame('Acme', $updated->accountLabel);
        self::assertSame('keep or clear', $updated->note);

        // Explicitly clear the note (hasNote = true, note = null).
        $cleared = $useCase->execute('01DEALAAAAAAAAAAAAAAAAAAAAA', new UpdateDealInput(hasNote: true, note: null));
        self::assertNull($cleared->note);
        self::assertSame(5000, $cleared->amountCents);
    }

    public function test_unknown_deal_throws(): void
    {
        $useCase = new UpdateDealUseCase(new InMemoryDealRepository());

        $this->expectException(DealNotFoundException::class);
        $useCase->execute('01MISSING000000000000000AA', new UpdateDealInput(amountCents: 1));
    }
}
