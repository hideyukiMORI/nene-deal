<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Install;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Pins the residual-window closure for the well-known dev credentials (#109 /
 * audit #91 (k)): the real phinx migrations, run as a subprocess the way the
 * manual deploy path does (docs/demo.md §5), must NOT seed
 * `operator@nene-deal.test` unless NENE_DEAL_SEED_DEV_OPERATOR=1 is set.
 */
final class SeedDefaultOperatorGuardTest extends TestCase
{
    private const DEV_SEED_EMAIL = 'operator@nene-deal.test';

    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/' . uniqid('deal-seed-guard-', true) . '.sqlite';
    }

    protected function tearDown(): void
    {
        @unlink($this->dbPath);
    }

    public function test_manual_migrate_path_seeds_no_account_without_the_flag(): void
    {
        $this->runMigrations(seedDevOperator: false);

        self::assertSame(0, $this->countUsers(self::DEV_SEED_EMAIL), 'No well-known account may be seeded without the opt-in flag.');
        self::assertSame(1, $this->countOrganizations(), 'The default organization seed itself must be unaffected.');
    }

    public function test_dev_opt_in_still_seeds_the_local_account_as_admin(): void
    {
        $this->runMigrations(seedDevOperator: true);

        self::assertSame(1, $this->countUsers(self::DEV_SEED_EMAIL));

        $pdo = new PDO('sqlite:' . $this->dbPath);
        $role = $pdo->query("SELECT role FROM users WHERE email = '" . self::DEV_SEED_EMAIL . "'");
        self::assertNotFalse($role);
        self::assertSame('admin', $role->fetchColumn(), 'The follow-up role-promotion migration still applies to the opted-in seed.');
    }

    private function runMigrations(bool $seedDevOperator): void
    {
        $root = dirname(__DIR__, 2);
        $command = [PHP_BINARY, $root . '/vendor/bin/phinx', 'migrate', '-c', $root . '/phinx.php'];

        // Explicit env wins over the repo's .env: Dotenv::safeLoad() is immutable.
        $env = [
            'DB_ADAPTER' => 'sqlite',
            'DB_NAME' => $this->dbPath,
            'PATH' => (string) getenv('PATH'),
        ];

        if ($seedDevOperator) {
            $env['NENE_DEAL_SEED_DEV_OPERATOR'] = '1';
        }

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $env);
        self::assertIsResource($process);

        stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        self::assertSame(0, $exitCode, 'phinx migrate failed: ' . $stderr);
    }

    private function countUsers(string $email): int
    {
        $pdo = new PDO('sqlite:' . $this->dbPath);
        $statement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        self::assertNotFalse($statement);
        $statement->execute([$email]);

        return (int) $statement->fetchColumn();
    }

    private function countOrganizations(): int
    {
        $pdo = new PDO('sqlite:' . $this->dbPath);
        $statement = $pdo->query('SELECT COUNT(*) FROM organizations');
        self::assertNotFalse($statement);

        return (int) $statement->fetchColumn();
    }
}
