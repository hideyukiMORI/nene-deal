<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Demo;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Runs the real `tools/sweep-demo.php` as a subprocess with the host timezone
 * pinned to Asia/Tokyo — the production (JST) configuration where the fleet
 * hit this twice (clear #280 / vault #143): `created_at` is written in UTC,
 * so a bare `DateTimeImmutable` parse reads every org as 9 hours older than
 * it is and the hourly cron reaps freshly provisioned demo orgs on the spot.
 * The script must parse UTC explicitly: fresh orgs survive, genuinely expired
 * ones are still reaped, and a UTC host behaves identically (#105, #91 (i)).
 */
final class SweepDemoScriptTest extends TestCase
{
    private string $dbPath;
    private PdoDatabaseQueryExecutor $query;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/' . uniqid('deal-sweep-script-', true) . '.sqlite';

        $config = new DatabaseConfig(
            url: null,
            environment: 'test',
            adapter: 'sqlite',
            host: 'localhost',
            port: 1,
            name: $this->dbPath,
            user: 'sqlite',
            password: '',
            charset: 'utf8',
        );
        $factory = new PdoConnectionFactory($config);
        $pdo = $factory->create();

        $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema/deal_domain.sql');
        self::assertIsString($schema);
        $pdo->exec($schema);

        $this->query = new PdoDatabaseQueryExecutor($factory, $pdo);
    }

    protected function tearDown(): void
    {
        @unlink($this->dbPath);
    }

    public function test_fresh_org_survives_and_expired_org_is_reaped_on_a_jst_host(): void
    {
        $nowUtc = time();
        $this->insertOrg('demo-fresh', gmdate('Y-m-d H:i:s', $nowUtc - 60));          // 1 minute old
        $this->insertOrg('demo-expired', gmdate('Y-m-d H:i:s', $nowUtc - 4 * 3600));  // past the 3h TTL
        $this->insertOrg('default', gmdate('Y-m-d H:i:s', $nowUtc - 30 * 24 * 3600)); // fixed showcase org

        $output = $this->runSweep('Asia/Tokyo');

        self::assertStringContainsString('2 org(s) total, 1 expired, 0 overflow, 1 reaped', $output);
        self::assertSame(['default', 'demo-fresh'], $this->remainingSlugs());
    }

    public function test_behaves_identically_on_a_utc_host(): void
    {
        $nowUtc = time();
        $this->insertOrg('demo-fresh', gmdate('Y-m-d H:i:s', $nowUtc - 60));
        $this->insertOrg('demo-expired', gmdate('Y-m-d H:i:s', $nowUtc - 4 * 3600));

        $output = $this->runSweep('UTC');

        self::assertStringContainsString('2 org(s) total, 1 expired, 0 overflow, 1 reaped', $output);
        self::assertSame(['demo-fresh'], $this->remainingSlugs());
    }

    private function insertOrg(string $slug, string $createdAtUtc): void
    {
        $this->query->execute(
            "INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES (?, ?, 'Demo', ?, ?)",
            [(string) new Ulid(), $slug, $createdAtUtc, $createdAtUtc],
        );
    }

    /** @return list<string> remaining org slugs, sorted */
    private function remainingSlugs(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['slug'],
            $this->query->fetchAll('SELECT slug FROM organizations ORDER BY slug', []),
        );
    }

    private function runSweep(string $timezone): string
    {
        $root = dirname(__DIR__, 2);
        $command = [
            PHP_BINARY,
            '-d', 'date.timezone=' . $timezone,
            $root . '/tools/sweep-demo.php',
        ];

        // Explicit env wins over the repo's .env: Dotenv::safeLoad() is immutable.
        $env = [
            'DB_ADAPTER' => 'sqlite',
            'DB_NAME' => $this->dbPath,
            'DEMO_TTL_HOURS' => '3',
            'DEMO_MAX_ORGS' => '200',
            'PATH' => (string) getenv('PATH'),
        ];

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $env);
        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        self::assertSame(0, $exitCode, 'sweep-demo.php failed: ' . $stderr);

        return $stdout;
    }
}
