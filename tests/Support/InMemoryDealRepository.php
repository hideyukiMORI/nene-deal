<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealActivity;
use NeneDeal\Deal\DealFilter;
use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Deal\DealRepositoryInterface;

final class InMemoryDealRepository implements DealRepositoryInterface
{
    /** @var array<string, Deal> */
    private array $deals = [];

    /** @var array<string, string> deal id => deleted_at */
    private array $deleted = [];

    /** @var list<DealActivity> */
    public array $activity = [];

    public function findById(string $id): ?Deal
    {
        if (isset($this->deleted[$id])) {
            return null;
        }

        return $this->deals[$id] ?? null;
    }

    public function findByIdIncludingDeleted(string $id): ?Deal
    {
        return $this->deals[$id] ?? null;
    }

    /** @return list<Deal> */
    public function findAll(DealFilter $filter, int $limit, int $offset): array
    {
        $all = array_values(array_filter($this->deals, function (Deal $deal) use ($filter): bool {
            if (!$filter->includeDeleted && isset($this->deleted[$deal->id])) {
                return false;
            }

            if ($filter->stageRef !== null && $deal->stageId !== $filter->stageRef && $deal->stageSlug !== $filter->stageRef) {
                return false;
            }

            if ($filter->ownerUserId !== null && $deal->ownerUserId !== $filter->ownerUserId) {
                return false;
            }

            if ($filter->query !== null && stripos($deal->accountLabel, $filter->query) === false) {
                return false;
            }

            return true;
        }));

        usort($all, static fn (Deal $a, Deal $b): int => strcmp($a->id, $b->id));

        return array_slice($all, $offset, $limit);
    }

    public function save(Deal $deal): void
    {
        $this->deals[$deal->id] = $deal;
    }

    public function update(Deal $deal): void
    {
        if (!isset($this->deals[$deal->id]) || isset($this->deleted[$deal->id])) {
            throw new DealNotFoundException($deal->id);
        }

        $this->deals[$deal->id] = $deal;
    }

    public function delete(string $id, ?string $actorUserId = null): void
    {
        if (!isset($this->deals[$id]) || isset($this->deleted[$id])) {
            throw new DealNotFoundException($id);
        }

        $this->deleted[$id] = date('Y-m-d H:i:s');
    }

    public function restore(string $id): void
    {
        if (!isset($this->deals[$id])) {
            throw new DealNotFoundException($id);
        }

        unset($this->deleted[$id]);
    }

    public function markHandedOff(string $id, int $invoiceClientId, int $invoiceQuoteId, string $handoffAt): void
    {
        $deal = $this->findById($id);

        if ($deal === null) {
            throw new DealNotFoundException($id);
        }

        $this->deals[$id] = new Deal(
            id: $deal->id,
            accountLabel: $deal->accountLabel,
            amountCents: $deal->amountCents,
            stageId: $deal->stageId,
            probabilityPercent: $deal->probabilityPercent,
            expectedCloseDate: $deal->expectedCloseDate,
            ownerUserId: $deal->ownerUserId,
            note: $deal->note,
            invoiceClientId: $invoiceClientId,
            invoiceQuoteId: $invoiceQuoteId,
            handoffAt: $handoffAt,
            organizationId: $deal->organizationId,
            stageSlug: $deal->stageSlug,
            createdAt: $deal->createdAt,
            updatedAt: $deal->updatedAt,
            ownerLabel: $deal->ownerLabel,
        );
    }

    public function recordActivity(DealActivity $activity): void
    {
        $this->activity[] = $activity;
    }

    /** @return list<Deal> */
    public function findForBoard(?string $ownerUserId, bool $includeDeleted = false): array
    {
        $all = array_values(array_filter(
            $this->deals,
            fn (Deal $deal): bool => ($includeDeleted || !isset($this->deleted[$deal->id]))
                && ($ownerUserId === null || $deal->ownerUserId === $ownerUserId),
        ));

        usort($all, static fn (Deal $a, Deal $b): int => strcmp($a->id, $b->id));

        return $all;
    }

    /** @return list<Deal> */
    public function findInMonth(string $startDate, string $endDate): array
    {
        $all = array_values(array_filter(
            $this->deals,
            fn (Deal $deal): bool => !isset($this->deleted[$deal->id])
                && $deal->expectedCloseDate !== null
                && $deal->expectedCloseDate >= $startDate
                && $deal->expectedCloseDate <= $endDate,
        ));

        usort($all, static fn (Deal $a, Deal $b): int => strcmp($a->id, $b->id));

        return $all;
    }

    /** @return list<DealActivity> */
    public function findActivity(string $dealId): array
    {
        $entries = array_values(array_filter(
            $this->activity,
            static fn (DealActivity $entry): bool => $entry->dealId === $dealId,
        ));

        return array_reverse($entries);
    }
}
