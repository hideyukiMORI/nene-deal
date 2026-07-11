<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds an account status to operator users so an account can be disabled
 * without deleting the row (deleting would orphan
 * deal_stage_history.actor_user_id attribution). Existing rows default to
 * 'active'. See #90.
 */
final class AddStatusToUsers extends AbstractMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('status', 'string', [
                'limit' => 32,
                'null' => false,
                'default' => 'active',
                'after' => 'role',
            ])
            ->update();
    }
}
