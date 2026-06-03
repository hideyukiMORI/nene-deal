<?php

declare(strict_types=1);

namespace NeneDeal\Audit;

/**
 * Read model for exporting the organization's audit trail. Scoped to the
 * current organization via tenancy; callers never pass an organization id.
 */
interface AuditExportRepositoryInterface
{
    /**
     * Activity entries whose `created_at` falls within the inclusive datetime
     * range, oldest first.
     *
     * @return list<AuditExportRow>
     */
    public function findInRange(string $startDateTime, string $endDateTime): array;
}
