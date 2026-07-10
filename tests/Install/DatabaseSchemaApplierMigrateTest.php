<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Install;

use Nene2\Install\DatabaseSchemaApplier;
use NeneDeal\Install\AdminProvisioner;
use PDO;
use Phinx\Config\Config;
use PHPUnit\Framework\TestCase;

/**
 * Proves the installer's migrate path runs the real phinx migrations in-process
 * via {@see DatabaseSchemaApplier} (phinx's Manager API), with no shell-out and no
 * dev-only dependency — phinx lives in `require` (vault #31 lesson), so a
 * `--no-dev` production build still autoloads it.
 *
 * Also pins the deal-specific installer premise: the migrations seed the default
 * organization AND the dev operator, which is why the provisioning probe must
 * ignore that account and why AdminProvisioner deletes it.
 */
final class DatabaseSchemaApplierMigrateTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/deal-migrate-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0770, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);
    }

    public function test_phinx_migrations_apply_in_process(): void
    {
        $dbPath = $this->dir . '/nene_deal.sqlite';

        $output = (new DatabaseSchemaApplier())->apply(new Config([
            'paths' => ['migrations' => dirname(__DIR__, 2) . '/database/migrations'],
            'environments' => [
                'default_environment' => 'install',
                'install' => [
                    'adapter' => 'sqlite',
                    'name' => $dbPath,
                    'suffix' => '',
                ],
            ],
            'version_order' => 'creation',
        ]));

        self::assertStringContainsString('CreateUsersTable', $output);

        // Re-open the migrated database and confirm the schema landed.
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        self::assertNotFalse($statement);
        $tables = $statement->fetchAll(PDO::FETCH_COLUMN);

        self::assertContains('organizations', $tables);
        self::assertContains('pipeline_stages', $tables);
        self::assertContains('deals', $tables);
        self::assertContains('deal_stage_history', $tables);
        self::assertContains('users', $tables);

        // The migrations seed the default org + the dev operator: the premise
        // behind the probe's dev-seed exclusion and AdminProvisioner's delete.
        $org = $pdo->query("SELECT COUNT(*) FROM organizations WHERE slug = 'default'");
        self::assertNotFalse($org);
        self::assertSame(1, (int) $org->fetchColumn());

        $devSeed = $pdo->prepare('SELECT role FROM users WHERE email = ?');
        $devSeed->execute([AdminProvisioner::DEV_SEED_EMAIL]);
        self::assertSame('admin', $devSeed->fetchColumn(), 'The dev operator seed (admin role) is expected after migrate.');
    }
}
