<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Append-only audit trail for all mutating operations (#89), consumed via
 * NENE2 `Nene2\Audit` (canonical AuditTableConfig shape, ADR 0005).
 *
 * `actor_id` / `organization_id` are ULID strings (26) — deal's PKs are ULIDs
 * (see ADR 0006). `organization_id` is nullable only for non-tenant events
 * (failed logins, where recording the would-be org could leak account
 * existence).
 */
final class CreateAuditEventsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('audit_events')
            ->addColumn('action', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('entity_type', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('entity_id', 'string', ['limit' => 64, 'null' => true, 'default' => null])
            ->addColumn('actor_id', 'string', ['limit' => 26, 'null' => true, 'default' => null])
            ->addColumn('organization_id', 'string', ['limit' => 26, 'null' => true, 'default' => null])
            ->addColumn('before_json', 'text', ['null' => true, 'default' => null])
            ->addColumn('after_json', 'text', ['null' => true, 'default' => null])
            ->addColumn('metadata_json', 'text', ['null' => true, 'default' => null])
            ->addColumn('occurred_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id'], ['name' => 'idx_audit_events_org'])
            ->addIndex(['entity_type', 'entity_id'], ['name' => 'idx_audit_events_entity'])
            ->addIndex(['action'], ['name' => 'idx_audit_events_action'])
            ->addIndex(['actor_id'], ['name' => 'idx_audit_events_actor'])
            ->addIndex(['occurred_at'], ['name' => 'idx_audit_events_occurred_at'])
            ->create();
    }
}
