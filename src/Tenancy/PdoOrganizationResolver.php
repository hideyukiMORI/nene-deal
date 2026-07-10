<?php

declare(strict_types=1);

namespace NeneDeal\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Throwable;

final readonly class PdoOrganizationResolver implements OrganizationResolver
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function soleId(): ?string
    {
        // Runs on every header-less request (including infra routes like
        // /health). Fail soft: an unreadable/absent organizations table simply
        // means "no sole organization", leaving tenant resolution unset.
        try {
            $rows = $this->query->fetchAll('SELECT id FROM organizations ORDER BY created_at ASC, id ASC LIMIT 2');
        } catch (Throwable) {
            return null;
        }

        if (count($rows) !== 1) {
            return null;
        }

        $id = $rows[0]['id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function findIdBySlug(string $slug): ?string
    {
        $row = $this->query->fetchOne('SELECT id FROM organizations WHERE slug = ? LIMIT 1', [$slug]);

        if ($row === null) {
            return null;
        }

        $id = $row['id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function existsById(string $id): bool
    {
        return $this->query->fetchOne('SELECT 1 FROM organizations WHERE id = ? LIMIT 1', [$id]) !== null;
    }
}
