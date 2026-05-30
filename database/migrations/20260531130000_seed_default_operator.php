<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Symfony\Component\Uid\Ulid;

/**
 * Seeds a default operator account in the default organization for local
 * development. Credentials: operator@nene-deal.test / password (change in
 * any real deployment).
 */
final class SeedDefaultOperator extends AbstractMigration
{
    public function up(): void
    {
        $org = $this->fetchRow("SELECT id FROM organizations WHERE slug = 'default'");

        if ($org === false || !isset($org['id']) || !is_string($org['id'])) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->table('users')->insert([
            'id' => (string) new Ulid(),
            'organization_id' => $org['id'],
            'email' => 'operator@nene-deal.test',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'operator',
            'created_at' => $now,
            'updated_at' => $now,
        ])->saveData();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM users WHERE email = 'operator@nene-deal.test'");
    }
}
