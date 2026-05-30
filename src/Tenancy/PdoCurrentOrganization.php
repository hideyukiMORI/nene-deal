<?php

declare(strict_types=1);

namespace NeneDeal\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use RuntimeException;

/**
 * Single-organization resolver: returns the sole seeded organization id,
 * resolved once on first use and cached for the process.
 */
final class PdoCurrentOrganization implements CurrentOrganization
{
    private ?string $cachedId = null;

    public function __construct(
        private readonly DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function id(): string
    {
        if ($this->cachedId !== null) {
            return $this->cachedId;
        }

        $row = $this->query->fetchOne(
            'SELECT id FROM organizations ORDER BY created_at ASC, id ASC LIMIT 1',
        );

        if ($row === null || !isset($row['id']) || !is_string($row['id']) || $row['id'] === '') {
            throw new RuntimeException('No organization is provisioned. Run database migrations to seed the default organization.');
        }

        return $this->cachedId = $row['id'];
    }
}
