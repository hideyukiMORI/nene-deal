<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDealsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('deals', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 26, 'null' => false])
            ->addColumn('organization_id', 'string', ['limit' => 26, 'null' => false])
            ->addColumn('account_label', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('amount_cents', 'biginteger', ['null' => false, 'default' => 0])
            ->addColumn('stage_id', 'string', ['limit' => 26, 'null' => false])
            ->addColumn('probability_percent', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('expected_close_date', 'date', ['null' => true, 'default' => null])
            ->addColumn('owner_user_id', 'string', ['limit' => 26, 'null' => true, 'default' => null])
            ->addColumn('note', 'text', ['null' => true, 'default' => null])
            ->addColumn('invoice_client_id', 'biginteger', ['null' => true, 'default' => null])
            ->addColumn('invoice_quote_id', 'biginteger', ['null' => true, 'default' => null])
            ->addColumn('handoff_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id', 'stage_id'], ['name' => 'idx_deals_org_stage'])
            ->addIndex(['organization_id', 'expected_close_date'], ['name' => 'idx_deals_org_close_date'])
            ->create();
    }
}
