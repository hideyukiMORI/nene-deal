<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Updates the seeded dev operator to `admin` role so the default account can
 * manage users out of the box. Real deployments should create a dedicated admin.
 */
final class UpdateDefaultOperatorRoleToAdmin extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("UPDATE users SET role = 'admin', updated_at = NOW() WHERE email = 'operator@nene-deal.test'");
    }

    public function down(): void
    {
        $this->execute("UPDATE users SET role = 'operator', updated_at = NOW() WHERE email = 'operator@nene-deal.test'");
    }
}
