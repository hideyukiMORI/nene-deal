<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Audit-everything + soft delete:
 *  - generalises `deal_stage_history` into a full activity log (action + JSON
 *    change diff; `to_stage_id` becomes nullable for non-stage events),
 *  - adds soft-delete columns to `deals` so deletes are recoverable (like won),
 *    and so the audit trail is never lost to a physical delete.
 */
final class AuditSoftdeleteAndOwner extends AbstractMigration
{
    public function up(): void
    {
        $this->table('deal_stage_history')
            ->addColumn('action', 'string', ['limit' => 32, 'null' => false, 'default' => 'stage_changed'])
            ->addColumn('changes', 'text', ['null' => true, 'default' => null])
            ->update();

        $this->table('deal_stage_history')
            ->changeColumn('to_stage_id', 'string', ['limit' => 26, 'null' => true, 'default' => null])
            ->update();

        $this->table('deals')
            ->addColumn('deleted_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('deleted_by', 'string', ['limit' => 26, 'null' => true, 'default' => null])
            ->addIndex(['organization_id', 'deleted_at'], ['name' => 'idx_deals_org_deleted'])
            ->update();
    }

    public function down(): void
    {
        $this->table('deals')
            ->removeIndexByName('idx_deals_org_deleted')
            ->removeColumn('deleted_at')
            ->removeColumn('deleted_by')
            ->update();

        $this->table('deal_stage_history')
            ->removeColumn('action')
            ->removeColumn('changes')
            ->update();
    }
}
