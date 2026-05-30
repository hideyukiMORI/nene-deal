<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePipelineStagesTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('pipeline_stages', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 26, 'null' => false])
            ->addColumn('organization_id', 'string', ['limit' => 26, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('label', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('is_terminal', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('is_won', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id', 'slug'], ['unique' => true, 'name' => 'uniq_pipeline_stages_org_slug'])
            ->addIndex(['organization_id', 'sort_order'], ['name' => 'idx_pipeline_stages_org_order'])
            ->create();
    }
}
