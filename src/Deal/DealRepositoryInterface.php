<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * Persistence for deals and their activity log. Every query is scoped to the
 * current organization ({@see \NeneDeal\Tenancy\CurrentOrganization}); callers
 * never pass an organization id, so cross-tenant access cannot be expressed.
 */
interface DealRepositoryInterface
{
    /** Active (non-deleted) deal by id, or null. */
    public function findById(string $id): ?Deal;

    /** Deal by id including soft-deleted ones (used to restore). */
    public function findByIdIncludingDeleted(string $id): ?Deal;

    /**
     * @return list<Deal> Up to $limit deals matching the filter, ordered by id ascending.
     */
    public function findAll(DealFilter $filter, int $limit, int $offset): array;

    /** Inserts a new deal in the current organization (organization forced from tenancy). */
    public function save(Deal $deal): void;

    /** @throws DealNotFoundException */
    public function update(Deal $deal): void;

    /** Soft-deletes a deal (sets deleted_at); recoverable via {@see restore()}. @throws DealNotFoundException */
    public function delete(string $id, ?string $actorUserId = null): void;

    /** Clears the soft-delete marker, bringing the deal back. @throws DealNotFoundException */
    public function restore(string $id): void;

    /**
     * Persists the Invoice link ids and handoff timestamp after a successful
     * won-deal handoff.
     *
     * @throws DealNotFoundException
     */
    public function markHandedOff(string $id, int $invoiceClientId, int $invoiceQuoteId, string $handoffAt): void;

    /** Appends an audit-trail entry for the deal. */
    public function recordActivity(DealActivity $activity): void;

    /** @return list<DealActivity> Newest first. */
    public function findActivity(string $dealId): array;

    /**
     * All deals for the kanban board (no pagination), optionally for one owner.
     * Soft-deleted deals are excluded unless $includeDeleted. Terminal-stage
     * exclusion is decided by the caller using stage metadata.
     *
     * @return list<Deal>
     */
    public function findForBoard(?string $ownerUserId, bool $includeDeleted = false): array;

    /**
     * Deals whose expected_close_date falls within the inclusive [start, end]
     * date range (YYYY-MM-DD). Soft-deleted deals are excluded. Terminal-stage
     * exclusion is decided by the caller.
     *
     * @return list<Deal>
     */
    public function findInMonth(string $startDate, string $endDate): array;
}
