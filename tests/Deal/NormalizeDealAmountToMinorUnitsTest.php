<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use PDO;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Pins the #81 data migration that moves `deals.amount_cents` from yen x100 to
 * the fleet canon (JPY minor unit = ¥1, so the column holds whole yen).
 *
 * Runs the real phinx migrations in-process against a throwaway SQLite file,
 * following the precedent in {@see \NeneDeal\Tests\Install\DatabaseSchemaApplierMigrateTest}.
 *
 * The pre-flight guard is the reason this test exists. `/100` is lossless only
 * while every row is a multiple of 100, and the API accepts any non-negative
 * integer — a row that never went through the frontend can and does carry a
 * non-multiple (four such rows were found in the local dev database on
 * 2026-08-22, left behind by manual security probing). "The guard is there" is
 * not the same claim as "the guard fires", so it is asserted directly.
 */
final class NormalizeDealAmountToMinorUnitsTest extends TestCase
{
    /** The last migration before the one under test. */
    private const PREVIOUS_VERSION = 20260711140000;

    private const VERSION = 20260822120000;

    private string $dir;

    private string $dbPath;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/deal-amount-migration-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0770, true);
        $this->dbPath = $this->dir . '/nene_deal.sqlite';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
    }

    public function test_it_divides_stored_amounts_by_one_hundred(): void
    {
        $this->migrateTo(self::PREVIOUS_VERSION);
        $this->insertDeal('deal-a', 62_000_000);
        $this->insertDeal('deal-b', 1_500_000);

        $this->migrateTo(self::VERSION);

        self::assertSame(620_000, $this->amountOf('deal-a'));
        self::assertSame(15_000, $this->amountOf('deal-b'));
    }

    public function test_rolling_back_restores_the_original_amounts(): void
    {
        $this->migrateTo(self::PREVIOUS_VERSION);
        $this->insertDeal('deal-a', 62_000_000);
        $this->migrateTo(self::VERSION);

        $this->rollbackTo(self::PREVIOUS_VERSION);

        self::assertSame(62_000_000, $this->amountOf('deal-a'));
    }

    public function test_it_refuses_to_run_when_an_amount_is_not_a_multiple_of_one_hundred(): void
    {
        $this->migrateTo(self::PREVIOUS_VERSION);
        $this->insertDeal('deal-ok', 62_000_000);
        $this->insertDeal('deal-odd', 123_456);

        $refusal = null;

        try {
            $this->migrateTo(self::VERSION);
        } catch (RuntimeException $exception) {
            $refusal = $exception;
        }

        self::assertNotNull($refusal, 'The migration was expected to refuse a non-multiple-of-100 amount.');
        self::assertStringContainsString('not a multiple of 100', $refusal->getMessage());
        self::assertStringContainsString('1 deal row(s)', $refusal->getMessage());

        // Refusing must leave every row untouched — including the ones that
        // would have converted cleanly. A partial migration is worse than none.
        self::assertSame(62_000_000, $this->amountOf('deal-ok'));
        self::assertSame(123_456, $this->amountOf('deal-odd'));
    }

    private function migrateTo(int $version): void
    {
        $this->manager()->migrate('test', $version);
    }

    private function rollbackTo(int $version): void
    {
        $this->manager()->rollback('test', $version);
    }

    private function manager(): Manager
    {
        $config = new Config([
            'paths' => ['migrations' => dirname(__DIR__, 2) . '/database/migrations'],
            'environments' => [
                'default_environment' => 'test',
                'test' => [
                    'adapter' => 'sqlite',
                    'name' => $this->dbPath,
                    'suffix' => '',
                ],
            ],
            'version_order' => 'creation',
        ]);

        return new Manager($config, new ArrayInput([]), new BufferedOutput());
    }

    private function pdo(): PDO
    {
        $pdo = new PDO('sqlite:' . $this->dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    private function insertDeal(string $id, int $amountCents): void
    {
        $pdo = $this->pdo();
        $organizationId = $this->firstColumn($pdo, 'SELECT id FROM organizations LIMIT 1');
        $stageId = $this->firstColumn($pdo, 'SELECT id FROM pipeline_stages LIMIT 1');

        $statement = $pdo->prepare(
            'INSERT INTO deals (id, organization_id, account_label, amount_cents, stage_id,'
            . ' probability_percent, created_at, updated_at)'
            . " VALUES (:id, :org, :label, :amount, :stage, 50, '2026-08-22 00:00:00', '2026-08-22 00:00:00')",
        );
        $statement->execute([
            'id' => $id,
            'org' => $organizationId,
            'label' => $id,
            'amount' => $amountCents,
            'stage' => $stageId,
        ]);
    }

    private function amountOf(string $id): int
    {
        $statement = $this->pdo()->prepare('SELECT amount_cents FROM deals WHERE id = :id');
        $statement->execute(['id' => $id]);

        return (int) $statement->fetchColumn();
    }

    private function firstColumn(PDO $pdo, string $sql): string
    {
        $statement = $pdo->prepare($sql);
        $statement->execute();

        return (string) $statement->fetchColumn();
    }
}
