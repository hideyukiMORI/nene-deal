<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Install;

use Nene2\Install\ReInstallationGuard;
use NeneDeal\Install\AdminProvisioner;
use NeneDeal\Install\DatabaseProvisioningProbe;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Proves the re-installation guard refuses a second run: a present `.installed`
 * marker blocks outright, and — when the marker is missing — the DB probe blocks
 * once the target holds a REAL user. Deal nuance: the migrations seed a dev
 * operator (`operator@nene-deal.test`), so a target holding only that account is
 * still "not provisioned" — a half-finished install stays retryable.
 */
final class DatabaseProvisioningProbeTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/deal-probe-' . bin2hex(random_bytes(6));
        mkdir($this->dir . '/var', 0770, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/{,var/,*/}*', GLOB_BRACE) ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir . '/var');
        @rmdir($this->dir);
    }

    /** @param list<string> $emails */
    private function makeSqlite(array $emails): string
    {
        $path = $this->dir . '/var/nene_deal.sqlite';
        $pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE users (id TEXT PRIMARY KEY, email TEXT)');

        foreach ($emails as $i => $email) {
            $statement = $pdo->prepare('INSERT INTO users (id, email) VALUES (?, ?)');
            $statement->execute([(string) $i, $email]);
        }

        return $path;
    }

    public function test_fresh_target_without_env_is_not_provisioned(): void
    {
        $probe = new DatabaseProvisioningProbe([], $this->dir);
        self::assertFalse($probe->isProvisioned());
    }

    public function test_sqlite_without_users_is_not_provisioned(): void
    {
        $this->makeSqlite([]);
        $probe = new DatabaseProvisioningProbe(
            ['DB_ADAPTER' => 'sqlite', 'DB_NAME' => 'var/nene_deal.sqlite'],
            $this->dir,
        );

        self::assertFalse($probe->isProvisioned());
    }

    public function test_dev_seed_operator_alone_is_not_provisioned(): void
    {
        $this->makeSqlite([AdminProvisioner::DEV_SEED_EMAIL]);
        $probe = new DatabaseProvisioningProbe(
            ['DB_ADAPTER' => 'sqlite', 'DB_NAME' => 'var/nene_deal.sqlite'],
            $this->dir,
        );

        self::assertFalse(
            $probe->isProvisioned(),
            'The migration-seeded dev operator must not count as a provisioned install.',
        );
    }

    public function test_real_admin_is_provisioned(): void
    {
        $this->makeSqlite([AdminProvisioner::DEV_SEED_EMAIL, 'admin@example.com']);
        $probe = new DatabaseProvisioningProbe(
            ['DB_ADAPTER' => 'sqlite', 'DB_NAME' => 'var/nene_deal.sqlite'],
            $this->dir,
        );

        self::assertTrue($probe->isProvisioned());
    }

    public function test_missing_sqlite_file_is_not_provisioned(): void
    {
        $probe = new DatabaseProvisioningProbe(
            ['DB_ADAPTER' => 'sqlite', 'DB_NAME' => 'var/does-not-exist.sqlite'],
            $this->dir,
        );

        self::assertFalse($probe->isProvisioned());
    }

    public function test_guard_blocks_when_marker_present(): void
    {
        $marker = $this->dir . '/var/.installed';
        $guard = new ReInstallationGuard($marker, new DatabaseProvisioningProbe([], $this->dir));

        self::assertFalse($guard->isBlocked());

        $guard->markInstalled('2026-07-10T00:00:00+00:00');

        self::assertTrue($guard->isBlocked());
        self::assertSame('marker_present', $guard->blockedReason());
    }

    public function test_guard_blocks_via_probe_when_marker_absent_but_db_provisioned(): void
    {
        $this->makeSqlite(['admin@example.com']);
        $guard = new ReInstallationGuard(
            $this->dir . '/var/.installed',
            new DatabaseProvisioningProbe(['DB_ADAPTER' => 'sqlite', 'DB_NAME' => 'var/nene_deal.sqlite'], $this->dir),
        );

        self::assertTrue($guard->isBlocked());
        self::assertSame('database_provisioned', $guard->blockedReason());
    }
}
