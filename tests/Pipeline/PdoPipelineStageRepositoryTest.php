<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Pipeline;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneDeal\Pipeline\PdoPipelineStageRepository;
use NeneDeal\Tenancy\FixedOrganization;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class PdoPipelineStageRepositoryTest extends TestCase
{
    private PdoPipelineStageRepository $repository;

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
        $orgId = (string) new Ulid();
        $now = '2026-05-30 00:00:00';

        $query->execute(
            'INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$orgId, 'default', 'Default', $now, $now],
        );
        foreach ([['lead', 1], ['proposal', 3], ['won', 5]] as [$slug, $order]) {
            $query->execute(
                'INSERT INTO pipeline_stages (id, organization_id, slug, label, sort_order, is_terminal, is_won, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [(string) new Ulid(), $orgId, $slug, ucfirst($slug), $order, $slug === 'won' ? 1 : 0, $slug === 'won' ? 1 : 0, $now, $now],
            );
        }

        $this->repository = new PdoPipelineStageRepository($query, new FixedOrganization($orgId));
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
}
