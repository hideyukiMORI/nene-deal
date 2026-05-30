<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * Persistence for deals and their stage history. Every query is scoped to the
 * current organization ({@see \NeneDeal\Tenancy\CurrentOrganization}); callers
 * never pass an organization id, so cross-tenant access cannot be expressed.
 */
interface DealRepositoryInterface
{
    public function findById(string $id): ?Deal;

    /**
     * @return list<Deal> Up to $limit deals matching the filter, ordered by id ascending.
     */
    public function findAll(DealFilter $filter, int $limit, int $offset): array;

    /** Inserts a new deal in the current organization (organization forced from tenancy). */
    public function save(Deal $deal): void;

    /** @throws DealNotFoundException */
    public function update(Deal $deal): void;

    /** @throws DealNotFoundException */
    public function delete(string $id): void;

    public function appendHistory(StageHistoryEntry $entry): void;

    /** @return list<StageHistoryEntry> Newest first. */
    public function findHistory(string $dealId): array;
}
