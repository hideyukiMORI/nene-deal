<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealActivity;
use NeneDeal\Deal\DealFilter;
use NeneDeal\Deal\PdoDealRepository;
use NeneDeal\Tenancy\FixedOrganization;
use NeneDeal\Tests\Support\FixedClock;
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

        $this->repository = new PdoDealRepository($this->query, new FixedOrganization($this->orgId), new FixedClock());
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

        $otherOrg = new PdoDealRepository($this->query, new FixedOrganization((string) new Ulid()), new FixedClock());
        self::assertNull($otherOrg->findById($id));
        self::assertNotNull($this->repository->findById($id));
    }

    public function test_delete_is_soft_and_keeps_the_activity_trail(): void
    {
        $id = (string) new Ulid();
        $this->repository->save(new Deal($id, 'Acme', 1000, $this->leadStageId, 10));
        $this->repository->recordActivity(new DealActivity((string) new Ulid(), $id, 'created', null, $this->leadStageId));

        self::assertCount(1, $this->repository->findActivity($id));

        $this->repository->delete($id, '01ACTOR00000000000000000AA');

        // Hidden from normal reads but recoverable, and the trail is preserved.
        self::assertNull($this->repository->findById($id));
        self::assertNotNull($this->repository->findByIdIncludingDeleted($id));
        self::assertCount(1, $this->repository->findActivity($id));

        $this->repository->restore($id);
        self::assertNotNull($this->repository->findById($id));
    }

    public function test_find_by_id_returns_null_for_unknown(): void
    {
        self::assertNull($this->repository->findById('01UNKNOWNDEAL000000000000AA'));
    }

    public function test_update_overwrites_mutable_fields(): void
    {
        $id = (string) new Ulid();
        $this->repository->save(new Deal($id, 'Original', 1000, $this->leadStageId, 10));

        $original = $this->repository->findById($id);
        self::assertNotNull($original);

        $this->repository->update(new Deal(
            id: $id,
            accountLabel: 'Updated',
            amountCents: 9999,
            stageId: $this->wonStageId,
            probabilityPercent: 100,
            note: 'Updated note',
            organizationId: $original->organizationId,
        ));

        $updated = $this->repository->findById($id);
        self::assertNotNull($updated);
        self::assertSame('Updated', $updated->accountLabel);
        self::assertSame(9999, $updated->amountCents);
        self::assertSame($this->wonStageId, $updated->stageId);
        self::assertSame(100, $updated->probabilityPercent);
        self::assertSame('Updated note', $updated->note);
    }

    public function test_mark_handed_off_persists_link_ids(): void
    {
        $id = (string) new Ulid();
        $this->repository->save(new Deal($id, 'Acme', 1000, $this->wonStageId, 100));

        $this->repository->markHandedOff($id, 42, 99, '2026-06-01 00:00:00');

        $deal = $this->repository->findById($id);
        self::assertNotNull($deal);
        self::assertSame(42, $deal->invoiceClientId);
        self::assertSame(99, $deal->invoiceQuoteId);
        self::assertSame('2026-06-01 00:00:00', $deal->handoffAt);
    }

    public function test_find_all_stage_filter_by_slug(): void
    {
        $this->repository->save(new Deal((string) new Ulid(), 'Lead deal', 1000, $this->leadStageId, 10));
        $this->repository->save(new Deal((string) new Ulid(), 'Won deal', 2000, $this->wonStageId, 100));

        $results = $this->repository->findAll(new DealFilter(stageRef: 'lead', includeTerminal: true), 50, 0);
        self::assertCount(1, $results);
        self::assertSame('Lead deal', $results[0]->accountLabel);
    }

    public function test_find_all_query_filter_case_insensitive(): void
    {
        $this->repository->save(new Deal((string) new Ulid(), 'Acme Corp', 1000, $this->leadStageId, 10));
        $this->repository->save(new Deal((string) new Ulid(), 'Globex', 2000, $this->leadStageId, 10));

        $results = $this->repository->findAll(new DealFilter(query: 'ACME'), 50, 0);
        self::assertCount(1, $results);
        self::assertSame('Acme Corp', $results[0]->accountLabel);
    }

    public function test_find_all_owner_filter(): void
    {
        $this->repository->save(new Deal((string) new Ulid(), 'Alice', 1000, $this->leadStageId, 10, ownerUserId: 'alice'));
        $this->repository->save(new Deal((string) new Ulid(), 'Bob', 2000, $this->leadStageId, 10, ownerUserId: 'bob'));

        $results = $this->repository->findAll(new DealFilter(ownerUserId: 'alice'), 50, 0);
        self::assertCount(1, $results);
        self::assertSame('Alice', $results[0]->accountLabel);
    }

    public function test_history_returned_newest_first(): void
    {
        $id = (string) new Ulid();
        $this->repository->save(new Deal($id, 'Acme', 1000, $this->leadStageId, 10));
        $this->repository->recordActivity(new DealActivity((string) new Ulid(), $id, 'created', null, $this->leadStageId, createdAt: '2026-01-01 00:00:00'));
        $this->repository->recordActivity(new DealActivity((string) new Ulid(), $id, 'stage_changed', $this->leadStageId, $this->wonStageId, createdAt: '2026-06-01 00:00:00'));

        $history = $this->repository->findActivity($id);
        self::assertCount(2, $history);
        self::assertSame($this->wonStageId, $history[0]->toStageId);
        self::assertSame($this->leadStageId, $history[1]->toStageId);
    }
}
