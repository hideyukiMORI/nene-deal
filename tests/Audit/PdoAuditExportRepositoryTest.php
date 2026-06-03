<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Audit;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneDeal\Audit\PdoAuditExportRepository;
use NeneDeal\Tenancy\FixedOrganization;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class PdoAuditExportRepositoryTest extends TestCase
{
    private PdoDatabaseQueryExecutor $query;
    private PdoAuditExportRepository $repository;
    private string $orgId;
    private string $dealId;
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
        $this->dealId = (string) new Ulid();
        $this->leadStageId = (string) new Ulid();
        $this->wonStageId = (string) new Ulid();
        $actorId = (string) new Ulid();
        $now = '2026-05-30 00:00:00';

        $this->query->execute(
            'INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$this->orgId, 'default', 'Default', $now, $now],
        );
        $this->query->execute(
            'INSERT INTO users (id, organization_id, email, password_hash, role, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$actorId, $this->orgId, 'alice@example.com', 'x', 'admin', $now, $now],
        );
        foreach ([[$this->leadStageId, 'lead', 'Lead', 1, 0, 0], [$this->wonStageId, 'won', 'Won', 5, 1, 1]] as $s) {
            $this->query->execute(
                'INSERT INTO pipeline_stages (id, organization_id, slug, label, sort_order, is_terminal, is_won, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$s[0], $this->orgId, $s[1], $s[2], $s[3], $s[4], $s[5], $now, $now],
            );
        }
        $this->query->execute(
            'INSERT INTO deals (id, organization_id, account_label, amount_cents, stage_id, probability_percent, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->dealId, $this->orgId, 'Acme Corp', 1000, $this->leadStageId, 50, $now, $now],
        );

        $this->repository = new PdoAuditExportRepository($this->query, new FixedOrganization($this->orgId));

        // In-range stage move (with actor) + an out-of-range entry.
        $this->query->execute(
            'INSERT INTO deal_stage_history (id, deal_id, from_stage_id, to_stage_id, action, changes, actor_user_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [(string) new Ulid(), $this->dealId, $this->leadStageId, $this->wonStageId, 'stage_changed', null, $actorId, '2026-06-10 09:00:00'],
        );
        $this->query->execute(
            'INSERT INTO deal_stage_history (id, deal_id, from_stage_id, to_stage_id, action, changes, actor_user_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [(string) new Ulid(), $this->dealId, null, null, 'updated', '{"amount_cents":{"from":1000,"to":2000}}', $actorId, '2026-07-02 00:00:00'],
        );
    }

    public function test_returns_enriched_rows_within_range_only(): void
    {
        $rows = $this->repository->findInRange('2026-06-01 00:00:00', '2026-06-30 23:59:59');

        self::assertCount(1, $rows);
        $row = $rows[0];
        self::assertSame('stage_changed', $row->action);
        self::assertSame('Acme Corp', $row->dealLabel);
        self::assertSame('alice@example.com', $row->actorLabel);
        self::assertSame('Lead', $row->fromStageLabel);
        self::assertSame('Won', $row->toStageLabel);
    }

    public function test_decodes_change_diff_and_orders_oldest_first(): void
    {
        $rows = $this->repository->findInRange('2026-06-01 00:00:00', '2026-07-31 23:59:59');

        self::assertCount(2, $rows);
        // Oldest first.
        self::assertSame('stage_changed', $rows[0]->action);
        self::assertSame('updated', $rows[1]->action);
        self::assertSame(['amount_cents' => ['from' => 1000, 'to' => 2000]], $rows[1]->changes);
    }
}
