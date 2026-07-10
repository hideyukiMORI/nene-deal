<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Install;

use DateTimeImmutable;
use Nene2\Http\ClockInterface;
use NeneDeal\Install\AdminProvisioner;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Proves post-migration provisioning: the seeded organization is renamed to the
 * operator-chosen name, the dev-seeded operator with WELL-KNOWN credentials is
 * deleted (a production install must not ship a backdoor account), and the real
 * admin is created with a PASSWORD_DEFAULT hash that `password_verify` accepts.
 * A failure rolls the whole transaction back so the target stays retryable.
 */
final class AdminProvisionerTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec('CREATE TABLE organizations (
            id TEXT PRIMARY KEY, slug TEXT NOT NULL, name TEXT NOT NULL,
            forecast_closing_day INTEGER, created_at TEXT NOT NULL, updated_at TEXT NOT NULL
        )');
        $this->pdo->exec('CREATE TABLE users (
            id TEXT PRIMARY KEY, organization_id TEXT NOT NULL, email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL, role TEXT NOT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL
        )');

        // Mirror the migration seeds: default org + dev operator (admin role).
        $this->pdo->exec("INSERT INTO organizations (id, slug, name, created_at, updated_at)
            VALUES ('01ORG', 'default', 'Default Organization', '2026-01-01 00:00:00', '2026-01-01 00:00:00')");
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $statement = $this->pdo->prepare("INSERT INTO users (id, organization_id, email, password_hash, role, created_at, updated_at)
            VALUES ('01DEV', '01ORG', ?, ?, 'admin', '2026-01-01 00:00:00', '2026-01-01 00:00:00')");
        $statement->execute([AdminProvisioner::DEV_SEED_EMAIL, $hash]);
    }

    private function provisioner(): AdminProvisioner
    {
        $clock = new class () implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-07-10T00:00:00Z');
            }
        };

        return new AdminProvisioner($this->pdo, $clock);
    }

    public function test_provision_renames_org_removes_dev_seed_and_creates_admin(): void
    {
        $result = $this->provisioner()->provision('株式会社ねね商事', 'admin@example.com', 'a-long-password-123');

        self::assertSame('01ORG', $result['organizationId']);
        self::assertTrue($result['devSeedRemoved']);

        $org = $this->pdo->query("SELECT name, slug FROM organizations WHERE id = '01ORG'");
        self::assertNotFalse($org);
        $orgRow = $org->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($orgRow);
        self::assertSame('株式会社ねね商事', $orgRow['name']);
        self::assertSame('default', $orgRow['slug'], 'The slug must stay stable — single-org resolution depends on it.');

        $devSeed = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $devSeed->execute([AdminProvisioner::DEV_SEED_EMAIL]);
        self::assertSame(0, (int) $devSeed->fetchColumn(), 'The well-known dev operator must be deleted.');

        $admin = $this->pdo->query("SELECT id, organization_id, password_hash, role FROM users WHERE email = 'admin@example.com'");
        self::assertNotFalse($admin);
        $adminRow = $admin->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($adminRow);
        self::assertSame($result['adminUserId'], $adminRow['id']);
        self::assertSame('01ORG', $adminRow['organization_id']);
        self::assertSame('admin', $adminRow['role']);
        self::assertIsString($adminRow['password_hash']);
        self::assertTrue(password_verify('a-long-password-123', $adminRow['password_hash']));
        self::assertStringNotContainsString('a-long-password-123', $adminRow['password_hash']);
    }

    public function test_admin_may_reuse_the_dev_seed_email_because_it_is_deleted_first(): void
    {
        $result = $this->provisioner()->provision('Acme', AdminProvisioner::DEV_SEED_EMAIL, 'a-long-password-123');

        $statement = $this->pdo->prepare('SELECT role, password_hash FROM users WHERE email = ?');
        $statement->execute([AdminProvisioner::DEV_SEED_EMAIL]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        self::assertIsArray($row);
        self::assertSame('admin', $row['role']);
        self::assertIsString($row['password_hash']);
        self::assertFalse(password_verify('password', $row['password_hash']), 'The well-known password must be gone.');
        self::assertTrue(password_verify('a-long-password-123', $row['password_hash']));
        self::assertTrue($result['devSeedRemoved']);
    }

    public function test_taken_email_throws_and_rolls_back(): void
    {
        $this->pdo->exec("INSERT INTO users (id, organization_id, email, password_hash, role, created_at, updated_at)
            VALUES ('01EXIST', '01ORG', 'admin@example.com', 'x', 'operator', '2026-01-01 00:00:00', '2026-01-01 00:00:00')");

        try {
            $this->provisioner()->provision('Acme', 'admin@example.com', 'a-long-password-123');
            self::fail('Expected a RuntimeException for the taken email.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('already exists', $exception->getMessage());
        }

        // Rolled back: the dev seed survives and the org name is unchanged.
        $devSeed = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $devSeed->execute([AdminProvisioner::DEV_SEED_EMAIL]);
        self::assertSame(1, (int) $devSeed->fetchColumn());

        $org = $this->pdo->query("SELECT name FROM organizations WHERE id = '01ORG'");
        self::assertNotFalse($org);
        self::assertSame('Default Organization', $org->fetchColumn());
    }

    public function test_missing_organization_throws(): void
    {
        $this->pdo->exec('DELETE FROM organizations');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No organization found');

        $this->provisioner()->provision('Acme', 'admin@example.com', 'a-long-password-123');
    }

    public function test_falls_back_to_oldest_organization_when_default_slug_is_absent(): void
    {
        $this->pdo->exec("UPDATE organizations SET slug = 'renamed' WHERE id = '01ORG'");

        $result = $this->provisioner()->provision('Acme', 'admin@example.com', 'a-long-password-123');

        self::assertSame('01ORG', $result['organizationId']);
    }
}
