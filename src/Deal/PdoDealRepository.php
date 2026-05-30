<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneDeal\Tenancy\CurrentOrganization;

final readonly class PdoDealRepository implements DealRepositoryInterface
{
    private const SELECT = 'd.id, d.organization_id, d.account_label, d.amount_cents, d.stage_id, '
        . 'd.probability_percent, d.expected_close_date, d.owner_user_id, d.note, '
        . 'd.invoice_client_id, d.invoice_quote_id, d.handoff_at, d.created_at, d.updated_at, '
        . 's.slug AS stage_slug';

    private const FROM = ' FROM deals d LEFT JOIN pipeline_stages s '
        . 'ON s.id = d.stage_id AND s.organization_id = d.organization_id';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private CurrentOrganization $organization,
    ) {
    }

    public function findById(string $id): ?Deal
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::SELECT . self::FROM . ' WHERE d.id = ? AND d.organization_id = ?',
            [$id, $this->organization->id()],
        );

        return $row !== null ? $this->mapRow($row) : null;
    }

    /** @return list<Deal> */
    public function findAll(DealFilter $filter, int $limit, int $offset): array
    {
        $conditions = ['d.organization_id = ?'];
        $params = [$this->organization->id()];

        if ($filter->stageRef !== null) {
            $conditions[] = '(d.stage_id = ? OR s.slug = ?)';
            $params[] = $filter->stageRef;
            $params[] = $filter->stageRef;
        }

        if ($filter->ownerUserId !== null) {
            $conditions[] = 'd.owner_user_id = ?';
            $params[] = $filter->ownerUserId;
        }

        if ($filter->query !== null) {
            $conditions[] = 'LOWER(d.account_label) LIKE ?';
            $params[] = '%' . strtolower($filter->query) . '%';
        }

        if (!$filter->includeTerminal) {
            $conditions[] = '(s.is_terminal = 0 OR s.is_terminal IS NULL)';
        }

        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->query->fetchAll(
            'SELECT ' . self::SELECT . self::FROM
            . ' WHERE ' . implode(' AND ', $conditions)
            . ' ORDER BY d.id ASC LIMIT ? OFFSET ?',
            $params,
        );

        return array_map(fn (array $row): Deal => $this->mapRow($row), $rows);
    }

    public function save(Deal $deal): void
    {
        $now = date('Y-m-d H:i:s');

        // The organization is forced from tenancy, never from the entity.
        $this->query->execute(
            'INSERT INTO deals (id, organization_id, account_label, amount_cents, stage_id, probability_percent,
                expected_close_date, owner_user_id, note, invoice_client_id, invoice_quote_id, handoff_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $deal->id,
                $this->organization->id(),
                $deal->accountLabel,
                $deal->amountCents,
                $deal->stageId,
                $deal->probabilityPercent,
                $deal->expectedCloseDate,
                $deal->ownerUserId,
                $deal->note,
                $deal->invoiceClientId,
                $deal->invoiceQuoteId,
                $deal->handoffAt,
                $now,
                $now,
            ],
        );
    }

    public function update(Deal $deal): void
    {
        $now = date('Y-m-d H:i:s');

        $affected = $this->query->execute(
            'UPDATE deals SET account_label = ?, amount_cents = ?, stage_id = ?, probability_percent = ?,
                expected_close_date = ?, owner_user_id = ?, note = ?, invoice_client_id = ?, invoice_quote_id = ?,
                handoff_at = ?, updated_at = ?
             WHERE id = ? AND organization_id = ?',
            [
                $deal->accountLabel,
                $deal->amountCents,
                $deal->stageId,
                $deal->probabilityPercent,
                $deal->expectedCloseDate,
                $deal->ownerUserId,
                $deal->note,
                $deal->invoiceClientId,
                $deal->invoiceQuoteId,
                $deal->handoffAt,
                $now,
                $deal->id,
                $this->organization->id(),
            ],
        );

        if ($affected === 0 && $this->findById($deal->id) === null) {
            throw new DealNotFoundException($deal->id);
        }
    }

    public function delete(string $id): void
    {
        if ($this->findById($id) === null) {
            throw new DealNotFoundException($id);
        }

        $this->query->execute(
            'DELETE FROM deals WHERE id = ? AND organization_id = ?',
            [$id, $this->organization->id()],
        );
    }

    public function appendHistory(StageHistoryEntry $entry): void
    {
        $this->query->execute(
            'INSERT INTO deal_stage_history (id, deal_id, from_stage_id, to_stage_id, actor_user_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $entry->id,
                $entry->dealId,
                $entry->fromStageId,
                $entry->toStageId,
                $entry->actorUserId,
                $entry->createdAt ?? date('Y-m-d H:i:s'),
            ],
        );
    }

    /** @return list<Deal> */
    public function findForBoard(?string $ownerUserId): array
    {
        $conditions = ['d.organization_id = ?'];
        $params = [$this->organization->id()];

        if ($ownerUserId !== null) {
            $conditions[] = 'd.owner_user_id = ?';
            $params[] = $ownerUserId;
        }

        $rows = $this->query->fetchAll(
            'SELECT ' . self::SELECT . self::FROM
            . ' WHERE ' . implode(' AND ', $conditions)
            . ' ORDER BY d.id ASC',
            $params,
        );

        return array_map(fn (array $row): Deal => $this->mapRow($row), $rows);
    }

    /** @return list<Deal> */
    public function findInMonth(string $startDate, string $endDate): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::SELECT . self::FROM
            . ' WHERE d.organization_id = ? AND d.expected_close_date IS NOT NULL'
            . ' AND d.expected_close_date BETWEEN ? AND ?'
            . ' ORDER BY d.id ASC',
            [$this->organization->id(), $startDate, $endDate],
        );

        return array_map(fn (array $row): Deal => $this->mapRow($row), $rows);
    }

    /** @return list<StageHistoryEntry> */
    public function findHistory(string $dealId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT h.id, h.deal_id, h.from_stage_id, h.to_stage_id, h.actor_user_id, h.created_at
             FROM deal_stage_history h
             JOIN deals d ON d.id = h.deal_id
             WHERE h.deal_id = ? AND d.organization_id = ?
             ORDER BY h.created_at DESC, h.id DESC',
            [$dealId, $this->organization->id()],
        );

        return array_map(
            static fn (array $row): StageHistoryEntry => new StageHistoryEntry(
                id: (string) $row['id'],
                dealId: (string) $row['deal_id'],
                fromStageId: isset($row['from_stage_id']) && $row['from_stage_id'] !== '' ? (string) $row['from_stage_id'] : null,
                toStageId: (string) $row['to_stage_id'],
                actorUserId: isset($row['actor_user_id']) && $row['actor_user_id'] !== '' ? (string) $row['actor_user_id'] : null,
                createdAt: (string) $row['created_at'],
            ),
            $rows,
        );
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): Deal
    {
        return new Deal(
            id: (string) $row['id'],
            accountLabel: (string) $row['account_label'],
            amountCents: (int) $row['amount_cents'],
            stageId: (string) $row['stage_id'],
            probabilityPercent: (int) $row['probability_percent'],
            expectedCloseDate: isset($row['expected_close_date']) && $row['expected_close_date'] !== '' ? (string) $row['expected_close_date'] : null,
            ownerUserId: isset($row['owner_user_id']) && $row['owner_user_id'] !== '' ? (string) $row['owner_user_id'] : null,
            note: isset($row['note']) && $row['note'] !== '' ? (string) $row['note'] : null,
            invoiceClientId: isset($row['invoice_client_id']) ? (int) $row['invoice_client_id'] : null,
            invoiceQuoteId: isset($row['invoice_quote_id']) ? (int) $row['invoice_quote_id'] : null,
            handoffAt: isset($row['handoff_at']) && $row['handoff_at'] !== '' ? (string) $row['handoff_at'] : null,
            organizationId: (string) $row['organization_id'],
            stageSlug: isset($row['stage_slug']) && $row['stage_slug'] !== '' ? (string) $row['stage_slug'] : null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
