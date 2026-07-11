<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Symfony\Component\Uid\Ulid;

/**
 * Seeds a default operator account in the default organization for local
 * development. Credentials: operator@nene-deal.test / password.
 *
 * Opt-in only (#109 / audit #91 (k)): the well-known account is seeded ONLY
 * when NENE_DEAL_SEED_DEV_OPERATOR=1 is set (the dev compose stack sets it).
 * Without the flag — including the manual CLI `phinx migrate` deploy path in
 * docs/demo.md — nothing is inserted, so no reachable box ever carries known
 * credentials. The browser installer additionally deletes the account when it
 * does exist (AdminProvisioner, #65); manual installs create their first
 * admin with `php tools/create-admin.php` instead.
 */
final class SeedDefaultOperator extends AbstractMigration
{
    public function up(): void
    {
        if (($_ENV['NENE_DEAL_SEED_DEV_OPERATOR'] ?? getenv('NENE_DEAL_SEED_DEV_OPERATOR')) !== '1') {
            return;
        }

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
