<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDealStageHistoryTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('deal_stage_history', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 26, 'null' => false])
            ->addColumn('deal_id', 'string', ['limit' => 26, 'null' => false])
            ->addColumn('from_stage_id', 'string', ['limit' => 26, 'null' => true, 'default' => null])
            ->addColumn('to_stage_id', 'string', ['limit' => 26, 'null' => false])
            ->addColumn('actor_user_id', 'string', ['limit' => 26, 'null' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addIndex(['deal_id', 'created_at'], ['name' => 'idx_deal_stage_history_deal'])
            ->addForeignKey('deal_id', 'deals', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
