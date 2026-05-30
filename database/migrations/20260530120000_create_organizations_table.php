<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOrganizationsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('organizations', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 26, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uniq_organizations_slug'])
            ->create();
    }
}
