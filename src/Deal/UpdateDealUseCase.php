<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use LogicException;

final readonly class UpdateDealUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
    ) {
    }

    /** @throws DealNotFoundException */
    public function execute(string $id, UpdateDealInput $input): Deal
    {
        $deal = $this->deals->findById($id);

        if ($deal === null) {
            throw new DealNotFoundException($id);
        }

        $this->deals->update(new Deal(
            id: $deal->id,
            accountLabel: $input->accountLabel ?? $deal->accountLabel,
            amountCents: $input->amountCents ?? $deal->amountCents,
            stageId: $deal->stageId,
            probabilityPercent: $input->probabilityPercent ?? $deal->probabilityPercent,
            expectedCloseDate: $input->hasExpectedCloseDate ? $input->expectedCloseDate : $deal->expectedCloseDate,
            ownerUserId: $input->hasOwnerUserId ? $input->ownerUserId : $deal->ownerUserId,
            note: $input->hasNote ? $input->note : $deal->note,
            invoiceClientId: $deal->invoiceClientId,
            invoiceQuoteId: $deal->invoiceQuoteId,
            handoffAt: $deal->handoffAt,
        ));

        $updated = $this->deals->findById($id);

        if ($updated === null) {
            throw new LogicException('Deal could not be loaded immediately after update.');
        }

        return $updated;
    }
}
