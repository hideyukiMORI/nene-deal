<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneDeal\Tenancy\CurrentOrganization;
use Symfony\Component\Uid\Ulid;

final readonly class PdoDealRepository implements DealRepositoryInterface
{
    private const SELECT = 'd.id, d.organization_id, d.account_label, d.amount_cents, d.stage_id, '
        . 'd.probability_percent, d.expected_close_date, d.owner_user_id, d.note, '
        . 'd.invoice_client_id, d.invoice_quote_id, d.handoff_at, d.created_at, d.updated_at, '
        . 'd.deleted_at, s.slug AS stage_slug, u.email AS owner_label';

    private const FROM = ' FROM deals d'
        . ' LEFT JOIN pipeline_stages s ON s.id = d.stage_id AND s.organization_id = d.organization_id'
        . ' LEFT JOIN users u ON u.id = d.owner_user_id';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private CurrentOrganization $organization,
    ) {
    }

    public function findById(string $id): ?Deal
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::SELECT . self::FROM
            . ' WHERE d.id = ? AND d.organization_id = ? AND d.deleted_at IS NULL',
            [$id, $this->organization->id()],
        );

        return $row !== null ? $this->mapRow($row) : null;
    }

    public function findByIdIncludingDeleted(string $id): ?Deal
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

        if (!$filter->includeDeleted) {
            $conditions[] = 'd.deleted_at IS NULL';
        }

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
             WHERE id = ? AND organization_id = ? AND deleted_at IS NULL',
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

    public function delete(string $id, ?string $actorUserId = null): void
    {
        if ($this->findById($id) === null) {
            throw new DealNotFoundException($id);
        }

        $now = date('Y-m-d H:i:s');
        $this->query->execute(
            'UPDATE deals SET deleted_at = ?, deleted_by = ?, updated_at = ?
             WHERE id = ? AND organization_id = ? AND deleted_at IS NULL',
            [$now, $actorUserId, $now, $id, $this->organization->id()],
        );
    }

    public function restore(string $id): void
    {
        if ($this->findByIdIncludingDeleted($id) === null) {
            throw new DealNotFoundException($id);
        }

        $this->query->execute(
            'UPDATE deals SET deleted_at = NULL, deleted_by = NULL, updated_at = ?
             WHERE id = ? AND organization_id = ?',
            [date('Y-m-d H:i:s'), $id, $this->organization->id()],
        );
    }

    public function markHandedOff(string $id, int $invoiceClientId, int $invoiceQuoteId, string $handoffAt): void
    {
        $affected = $this->query->execute(
            'UPDATE deals SET invoice_client_id = ?, invoice_quote_id = ?, handoff_at = ?, updated_at = ?
             WHERE id = ? AND organization_id = ? AND deleted_at IS NULL',
            [$invoiceClientId, $invoiceQuoteId, $handoffAt, date('Y-m-d H:i:s'), $id, $this->organization->id()],
        );

        if ($affected === 0 && $this->findById($id) === null) {
            throw new DealNotFoundException($id);
        }
    }

    public function recordActivity(DealActivity $activity): void
    {
        $this->query->execute(
            'INSERT INTO deal_stage_history (id, deal_id, from_stage_id, to_stage_id, action, changes, actor_user_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $activity->id !== '' ? $activity->id : (string) new Ulid(),
                $activity->dealId,
                $activity->fromStageId,
                $activity->toStageId,
                $activity->action,
                $activity->changes !== null ? json_encode($activity->changes) : null,
                $activity->actorUserId,
                $activity->createdAt ?? date('Y-m-d H:i:s'),
            ],
        );
    }

    /** @return list<Deal> */
    public function findForBoard(?string $ownerUserId, bool $includeDeleted = false): array
    {
        $conditions = ['d.organization_id = ?'];
        $params = [$this->organization->id()];

        if (!$includeDeleted) {
            $conditions[] = 'd.deleted_at IS NULL';
        }

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
            . ' WHERE d.organization_id = ? AND d.deleted_at IS NULL AND d.expected_close_date IS NOT NULL'
            . ' AND d.expected_close_date BETWEEN ? AND ?'
            . ' ORDER BY d.id ASC',
            [$this->organization->id(), $startDate, $endDate],
        );

        return array_map(fn (array $row): Deal => $this->mapRow($row), $rows);
    }

    /** @return list<DealActivity> */
    public function findActivity(string $dealId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT h.id, h.deal_id, h.from_stage_id, h.to_stage_id, h.action, h.changes, h.actor_user_id,
                    h.created_at, u.email AS actor_label
             FROM deal_stage_history h
             JOIN deals d ON d.id = h.deal_id
             LEFT JOIN users u ON u.id = h.actor_user_id
             WHERE h.deal_id = ? AND d.organization_id = ?
             ORDER BY h.created_at DESC, h.id DESC',
            [$dealId, $this->organization->id()],
        );

        return array_map(
            static function (array $row): DealActivity {
                $changes = null;
                if (isset($row['changes']) && is_string($row['changes']) && $row['changes'] !== '') {
                    $decoded = json_decode($row['changes'], true);
                    $changes = is_array($decoded) ? $decoded : null;
                }

                return new DealActivity(
                    id: (string) $row['id'],
                    dealId: (string) $row['deal_id'],
                    action: isset($row['action']) && $row['action'] !== '' ? (string) $row['action'] : 'stage_changed',
                    fromStageId: isset($row['from_stage_id']) && $row['from_stage_id'] !== '' ? (string) $row['from_stage_id'] : null,
                    toStageId: isset($row['to_stage_id']) && $row['to_stage_id'] !== '' ? (string) $row['to_stage_id'] : null,
                    actorUserId: isset($row['actor_user_id']) && $row['actor_user_id'] !== '' ? (string) $row['actor_user_id'] : null,
                    changes: $changes,
                    createdAt: (string) $row['created_at'],
                    actorLabel: isset($row['actor_label']) && $row['actor_label'] !== '' ? (string) $row['actor_label'] : null,
                );
            },
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
            ownerLabel: isset($row['owner_label']) && $row['owner_label'] !== '' ? (string) $row['owner_label'] : null,
            deletedAt: isset($row['deleted_at']) && $row['deleted_at'] !== '' ? (string) $row['deleted_at'] : null,
        );
    }
}
