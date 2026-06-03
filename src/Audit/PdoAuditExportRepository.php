<?php

declare(strict_types=1);

namespace NeneDeal\Audit;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneDeal\Tenancy\CurrentOrganization;

final readonly class PdoAuditExportRepository implements AuditExportRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private CurrentOrganization $organization,
    ) {
    }

    /** @return list<AuditExportRow> */
    public function findInRange(string $startDateTime, string $endDateTime): array
    {
        $rows = $this->query->fetchAll(
            'SELECT h.created_at, h.action, h.deal_id, d.account_label,
                    u.email AS actor_label, sf.label AS from_stage_label, st.label AS to_stage_label, h.changes
             FROM deal_stage_history h
             JOIN deals d ON d.id = h.deal_id
             LEFT JOIN users u ON u.id = h.actor_user_id
             LEFT JOIN pipeline_stages sf ON sf.id = h.from_stage_id
             LEFT JOIN pipeline_stages st ON st.id = h.to_stage_id
             WHERE d.organization_id = ? AND h.created_at BETWEEN ? AND ?
             ORDER BY h.created_at ASC, h.id ASC',
            [$this->organization->id(), $startDateTime, $endDateTime],
        );

        return array_map(
            static function (array $row): AuditExportRow {
                $changes = null;
                if (isset($row['changes']) && is_string($row['changes']) && $row['changes'] !== '') {
                    $decoded = json_decode($row['changes'], true);
                    $changes = is_array($decoded) ? $decoded : null;
                }

                return new AuditExportRow(
                    createdAt: (string) $row['created_at'],
                    action: isset($row['action']) && $row['action'] !== '' ? (string) $row['action'] : 'stage_changed',
                    dealId: (string) $row['deal_id'],
                    dealLabel: (string) $row['account_label'],
                    actorLabel: isset($row['actor_label']) && $row['actor_label'] !== '' ? (string) $row['actor_label'] : null,
                    fromStageLabel: isset($row['from_stage_label']) && $row['from_stage_label'] !== '' ? (string) $row['from_stage_label'] : null,
                    toStageLabel: isset($row['to_stage_label']) && $row['to_stage_label'] !== '' ? (string) $row['to_stage_label'] : null,
                    changes: $changes,
                );
            },
            $rows,
        );
    }
}
