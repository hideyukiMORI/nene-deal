<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use LogicException;
use NeneDeal\Pipeline\PipelineStageRepositoryInterface;
use Symfony\Component\Uid\Ulid;

final readonly class CreateDealUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
        private PipelineStageRepositoryInterface $stages,
    ) {
    }

    /** @throws UnknownStageException */
    public function execute(CreateDealInput $input): Deal
    {
        $stage = $this->stages->findByIdOrSlug($input->stageRef);

        if ($stage === null) {
            throw new UnknownStageException($input->stageRef);
        }

        $id = (string) new Ulid();

        $this->deals->save(new Deal(
            id: $id,
            accountLabel: $input->accountLabel,
            amountCents: $input->amountCents,
            stageId: $stage->id,
            probabilityPercent: $input->probabilityPercent,
            expectedCloseDate: $input->expectedCloseDate,
            ownerUserId: $input->ownerUserId,
            note: $input->note,
        ));

        $this->deals->appendHistory(new StageHistoryEntry(
            id: (string) new Ulid(),
            dealId: $id,
            fromStageId: null,
            toStageId: $stage->id,
            actorUserId: $input->ownerUserId,
        ));

        $created = $this->deals->findById($id);

        if ($created === null) {
            throw new LogicException('Deal could not be loaded immediately after creation.');
        }

        return $created;
    }
}
