<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealFilter;
use NeneDeal\Deal\DealNotFoundException;
use NeneDeal\Deal\DealRepositoryInterface;
use NeneDeal\Deal\StageHistoryEntry;

final class InMemoryDealRepository implements DealRepositoryInterface
{
    /** @var array<string, Deal> */
    private array $deals = [];

    /** @var list<StageHistoryEntry> */
    public array $history = [];

    public function findById(string $id): ?Deal
    {
        return $this->deals[$id] ?? null;
    }

    /** @return list<Deal> */
    public function findAll(DealFilter $filter, int $limit, int $offset): array
    {
        $all = array_values($this->deals);

        $all = array_values(array_filter($all, static function (Deal $deal) use ($filter): bool {
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
        if (!isset($this->deals[$deal->id])) {
            throw new DealNotFoundException($deal->id);
        }

        $this->deals[$deal->id] = $deal;
    }

    public function delete(string $id): void
    {
        if (!isset($this->deals[$id])) {
            throw new DealNotFoundException($id);
        }

        unset($this->deals[$id]);
        $this->history = array_values(array_filter(
            $this->history,
            static fn (StageHistoryEntry $entry): bool => $entry->dealId !== $id,
        ));
    }

    public function appendHistory(StageHistoryEntry $entry): void
    {
        $this->history[] = $entry;
    }

    /** @return list<Deal> */
    public function findForBoard(?string $ownerUserId): array
    {
        $all = array_values(array_filter(
            array_values($this->deals),
            static fn (Deal $deal): bool => $ownerUserId === null || $deal->ownerUserId === $ownerUserId,
        ));

        usort($all, static fn (Deal $a, Deal $b): int => strcmp($a->id, $b->id));

        return $all;
    }

    /** @return list<Deal> */
    public function findInMonth(string $startDate, string $endDate): array
    {
        $all = array_values(array_filter(
            array_values($this->deals),
            static fn (Deal $deal): bool => $deal->expectedCloseDate !== null
                && $deal->expectedCloseDate >= $startDate
                && $deal->expectedCloseDate <= $endDate,
        ));

        usort($all, static fn (Deal $a, Deal $b): int => strcmp($a->id, $b->id));

        return $all;
    }

    /** @return list<StageHistoryEntry> */
    public function findHistory(string $dealId): array
    {
        $entries = array_values(array_filter(
            $this->history,
            static fn (StageHistoryEntry $entry): bool => $entry->dealId === $dealId,
        ));

        return array_reverse($entries);
    }
}
