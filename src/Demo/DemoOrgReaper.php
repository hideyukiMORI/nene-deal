<?php

declare(strict_types=1);

namespace NeneDeal\Demo;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Demo\DisposableOrgReaperInterface;

/**
 * Destroys one disposable demo organization and everything it owns
 * (`Nene2\Demo` consumer, #69 — the teardown half of `tools/sweep-demo.php`
 * and the sweeper's TTL/overflow path).
 *
 * Child rows are disposable demo data, so they are bulk-DELETEd children →
 * parents (the framework deliberately does not cascade — design doc risk 1).
 * `deal_stage_history` carries no `organization_id` column, so it is deleted
 * through its deals. Soft-deleted deals are demo data too and are removed by
 * the same physical DELETE. Idempotent by contract — reaping an org that a
 * concurrent sweep already removed is success.
 *
 * The `int` handle comes from the process-local {@see DemoOrgHandles}
 * registry (Deal keys organizations by ULID; the framework module types ids
 * as `int`).
 */
final readonly class DemoOrgReaper implements DisposableOrgReaperInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private DemoOrgHandles $handles,
    ) {
    }

    public function reap(int $orgId): void
    {
        $organizationId = $this->handles->organizationId($orgId);

        $this->query->execute(
            'DELETE FROM deal_stage_history WHERE deal_id IN (SELECT id FROM deals WHERE organization_id = ?)',
            [$organizationId],
        );
        $this->query->execute('DELETE FROM deals WHERE organization_id = ?', [$organizationId]);
        $this->query->execute('DELETE FROM pipeline_stages WHERE organization_id = ?', [$organizationId]);
        $this->query->execute('DELETE FROM users WHERE organization_id = ?', [$organizationId]);
        $this->query->execute('DELETE FROM organizations WHERE id = ?', [$organizationId]);
    }
}
