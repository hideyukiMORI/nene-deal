<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealFilter;
use NeneDeal\Deal\PdoDealRepository;
use NeneDeal\Deal\StageHistoryEntry;
use NeneDeal\Tenancy\FixedOrganization;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class PdoDealRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $query;
    private PdoDealRepository $repository;
    private string $orgId;
    private string $leadStageId;
    private string $wonStageId;

    protected function setUp(): void
    {
        $config = new DatabaseConfig(
            url: null,
            environment: 'test',
            adapter: 'sqlite',
            host: 'localhost',
            port: 1,
            name: ':memory:',
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

        $this->orgId = (string) new Ulid();
        $this->leadStageId = (string) new Ulid();
        $this->wonStageId = (string) new Ulid();

        $now = '2026-05-30 00:00:00';
        $this->query->execute(
            'INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$this->orgId, 'default', 'Default', $now, $now],
        );
        $this->query->execute(
            'INSERT INTO pipeline_stages (id, organization_id, slug, label, sort_order, is_terminal, is_won, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->leadStageId, $this->orgId, 'lead', 'Lead', 1, 0, 0, $now, $now],
        );
        $this->query->execute(
            'INSERT INTO pipeline_stages (id, organization_id, slug, label, sort_order, is_terminal, is_won, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->wonStageId, $this->orgId, 'won', 'Won', 5, 1, 1, $now, $now],
        );

        $this->repository = new PdoDealRepository($this->query, new FixedOrganization($this->orgId));
    }

    public function test_saves_and_reads_back_with_stage_slug(): void
    {
        $id = (string) new Ulid();
        $this->repository->save(new Deal(
            id: $id,
            accountLabel: 'Acme Corp',
            amountCents: 150_000_00,
            stageId: $this->leadStageId,
            probabilityPercent: 30,
        ));

        $deal = $this->repository->findById($id);
        self::assertNotNull($deal);
        self::assertSame('Acme Corp', $deal->accountLabel);
        self::assertSame(150_000_00, $deal->amountCents);
        self::assertSame('lead', $deal->stageSlug);
        self::assertSame($this->orgId, $deal->organizationId);
    }

    public function test_terminal_stage_excluded_unless_requested(): void
    {
        $this->repository->save(new Deal((string) new Ulid(), 'Open', 1000, $this->leadStageId, 10));
        $this->repository->save(new Deal((string) new Ulid(), 'Closed', 2000, $this->wonStageId, 100));

        self::assertCount(1, $this->repository->findAll(new DealFilter(), 50, 0));
        self::assertCount(2, $this->repository->findAll(new DealFilter(includeTerminal: true), 50, 0));
    }

    public function test_is_scoped_to_organization(): void
    {
        $id = (string) new Ulid();
        $this->repository->save(new Deal($id, 'Acme', 1000, $this->leadStageId, 10));

        $otherOrg = new PdoDealRepository($this->query, new FixedOrganization((string) new Ulid()));
        self::assertNull($otherOrg->findById($id));
        self::assertNotNull($this->repository->findById($id));
    }

    public function test_history_cascades_on_delete(): void
    {
        $id = (string) new Ulid();
        $this->repository->save(new Deal($id, 'Acme', 1000, $this->leadStageId, 10));
        $this->repository->appendHistory(new StageHistoryEntry((string) new Ulid(), $id, null, $this->leadStageId));

        self::assertCount(1, $this->repository->findHistory($id));

        $this->repository->delete($id);

        $row = $this->query->fetchOne('SELECT COUNT(*) AS cnt FROM deal_stage_history WHERE deal_id = ?', [$id]);
        self::assertNotNull($row);
        self::assertSame(0, (int) $row['cnt']);
    }
}
