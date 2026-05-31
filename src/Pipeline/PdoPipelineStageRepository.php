<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeneDeal\Tenancy\CurrentOrganization;

final readonly class PdoPipelineStageRepository implements PipelineStageRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, slug, label, sort_order, is_terminal, is_won, created_at, updated_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private CurrentOrganization $organization,
    ) {
    }

    /** @return list<PipelineStage> */
    public function findAll(): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM pipeline_stages WHERE organization_id = ? ORDER BY sort_order ASC, id ASC',
            [$this->organization->id()],
        );

        return array_map(fn (array $row): PipelineStage => $this->mapRow($row), $rows);
    }

    public function findByIdOrSlug(string $idOrSlug): ?PipelineStage
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM pipeline_stages WHERE organization_id = ? AND (id = ? OR slug = ?) LIMIT 1',
            [$this->organization->id(), $idOrSlug, $idOrSlug],
        );

        return $row !== null ? $this->mapRow($row) : null;
    }

    public function findById(string $id): ?PipelineStage
    {
        $row = $this->query->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM pipeline_stages WHERE organization_id = ? AND id = ? LIMIT 1',
            [$this->organization->id(), $id],
        );

        return $row !== null ? $this->mapRow($row) : null;
    }

    public function save(PipelineStage $stage): void
    {
        $now = date('Y-m-d H:i:s');

        $exists = $this->findById($stage->id) !== null;

        if (!$exists) {
            $this->query->execute(
                'INSERT INTO pipeline_stages (id, organization_id, slug, label, sort_order, is_terminal, is_won, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $stage->id,
                    $stage->organizationId,
                    $stage->slug,
                    $stage->label,
                    $stage->sortOrder,
                    $stage->isTerminal ? 1 : 0,
                    $stage->isWon ? 1 : 0,
                    $stage->createdAt ?? $now,
                    $now,
                ],
            );
        } else {
            $this->query->execute(
                'UPDATE pipeline_stages SET label = ?, sort_order = ?, updated_at = ? WHERE organization_id = ? AND id = ?',
                [$stage->label, $stage->sortOrder, $now, $stage->organizationId, $stage->id],
            );
        }
    }

    public function delete(string $id): void
    {
        $this->query->execute(
            'DELETE FROM pipeline_stages WHERE organization_id = ? AND id = ?',
            [$this->organization->id(), $id],
        );
    }

    public function slugExists(string $slug): bool
    {
        $row = $this->query->fetchOne(
            'SELECT 1 FROM pipeline_stages WHERE organization_id = ? AND slug = ? LIMIT 1',
            [$this->organization->id(), $slug],
        );

        return $row !== null;
    }

    public function hasDeals(string $stageId): bool
    {
        $row = $this->query->fetchOne(
            'SELECT 1 FROM deals WHERE organization_id = ? AND stage_id = ? LIMIT 1',
            [$this->organization->id(), $stageId],
        );

        return $row !== null;
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): PipelineStage
    {
        return new PipelineStage(
            id: (string) $row['id'],
            organizationId: (string) $row['organization_id'],
            slug: (string) $row['slug'],
            label: (string) $row['label'],
            sortOrder: (int) $row['sort_order'],
            isTerminal: (bool) $row['is_terminal'],
            isWon: (bool) $row['is_won'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
