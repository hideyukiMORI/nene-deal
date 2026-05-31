<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Pipeline;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneDeal\Pipeline\PdoPipelineStageRepository;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Tenancy\FixedOrganization;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class PdoPipelineStageRepositoryTest extends TestCase
{
    private PdoPipelineStageRepository $repository;
    private string $orgId;

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

        $query = new PdoDatabaseQueryExecutor($factory, $pdo);
        $this->orgId = (string) new Ulid();
        $now = '2026-05-30 00:00:00';

        $query->execute(
            'INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$this->orgId, 'default', 'Default', $now, $now],
        );
        foreach ([['lead', 1], ['proposal', 3], ['won', 5]] as [$slug, $order]) {
            $query->execute(
                'INSERT INTO pipeline_stages (id, organization_id, slug, label, sort_order, is_terminal, is_won, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [(string) new Ulid(), $this->orgId, $slug, ucfirst($slug), $order, $slug === 'won' ? 1 : 0, $slug === 'won' ? 1 : 0, $now, $now],
            );
        }

        // Seed a deal in 'lead' to test hasDeals
        $query->execute(
            'INSERT INTO deals (id, organization_id, account_label, amount_cents, stage_id, probability_percent, created_at, updated_at)
             SELECT ?, ?, ?, ?, id, ?, ?, ? FROM pipeline_stages WHERE slug = ? AND organization_id = ? LIMIT 1',
            [(string) new Ulid(), $this->orgId, 'Seeded Deal', 1000, 50, $now, $now, 'lead', $this->orgId],
        );

        $this->repository = new PdoPipelineStageRepository($query, new FixedOrganization($this->orgId));
    }

    public function test_lists_in_sort_order(): void
    {
        $stages = $this->repository->findAll();

        self::assertCount(3, $stages);
        self::assertSame('lead', $stages[0]->slug);
        self::assertSame('proposal', $stages[1]->slug);
        self::assertSame('won', $stages[2]->slug);
    }

    public function test_finds_by_slug_or_id(): void
    {
        $bySlug = $this->repository->findByIdOrSlug('proposal');
        self::assertNotNull($bySlug);

        $byId = $this->repository->findByIdOrSlug($bySlug->id);
        self::assertNotNull($byId);
        self::assertSame('proposal', $byId->slug);

        self::assertNull($this->repository->findByIdOrSlug('nope'));
    }

    public function test_find_by_id_returns_null_for_unknown(): void
    {
        self::assertNull($this->repository->findById('01UNKNOWNSTAGE0000000000AA'));
    }

    public function test_save_creates_new_stage(): void
    {
        $id = (string) new Ulid();
        $stage = new PipelineStage($id, $this->orgId, 'custom', 'Custom Stage', 99, false, false);

        $this->repository->save($stage);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame('custom', $found->slug);
        self::assertSame('Custom Stage', $found->label);
        self::assertSame(99, $found->sortOrder);
    }

    public function test_save_updates_existing_stage_label_and_sort_order(): void
    {
        $original = $this->repository->findByIdOrSlug('proposal');
        self::assertNotNull($original);

        $updated = new PipelineStage(
            $original->id,
            $original->organizationId,
            $original->slug,
            'Renamed Proposal',
            77,
            $original->isTerminal,
            $original->isWon,
            $original->createdAt,
        );
        $this->repository->save($updated);

        $found = $this->repository->findById($original->id);
        self::assertNotNull($found);
        self::assertSame('Renamed Proposal', $found->label);
        self::assertSame(77, $found->sortOrder);
        self::assertSame('proposal', $found->slug); // slug immutable
    }

    public function test_delete_removes_stage(): void
    {
        $stage = $this->repository->findByIdOrSlug('proposal');
        self::assertNotNull($stage);

        $this->repository->delete($stage->id);

        self::assertNull($this->repository->findById($stage->id));
    }

    public function test_slug_exists_returns_true_for_known_slug(): void
    {
        self::assertTrue($this->repository->slugExists('lead'));
        self::assertTrue($this->repository->slugExists('won'));
    }

    public function test_slug_exists_returns_false_for_unknown_slug(): void
    {
        self::assertFalse($this->repository->slugExists('does-not-exist'));
    }

    public function test_has_deals_returns_true_when_stage_has_a_deal(): void
    {
        $lead = $this->repository->findByIdOrSlug('lead');
        self::assertNotNull($lead);
        self::assertTrue($this->repository->hasDeals($lead->id));
    }

    public function test_has_deals_returns_false_for_empty_stage(): void
    {
        $proposal = $this->repository->findByIdOrSlug('proposal');
        self::assertNotNull($proposal);
        self::assertFalse($this->repository->hasDeals($proposal->id));
    }
}
