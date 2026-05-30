<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Tenancy;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneDeal\Tenancy\PdoOrganizationResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class PdoOrganizationResolverTest extends TestCase
{
    private PdoDatabaseQueryExecutor $query;

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
    }

    private function insertOrg(string $slug): string
    {
        $id = (string) new Ulid();
        $this->query->execute(
            'INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$id, $slug, ucfirst($slug), '2026-05-31 00:00:00', '2026-05-31 00:00:00'],
        );

        return $id;
    }

    public function test_sole_id_returns_the_single_organization(): void
    {
        $id = $this->insertOrg('default');
        self::assertSame($id, (new PdoOrganizationResolver($this->query))->soleId());
    }

    public function test_sole_id_is_null_when_multiple_organizations_exist(): void
    {
        $this->insertOrg('default');
        $this->insertOrg('acme');
        self::assertNull((new PdoOrganizationResolver($this->query))->soleId());
    }

    public function test_find_id_by_slug(): void
    {
        $this->insertOrg('default');
        $acme = $this->insertOrg('acme');
        $resolver = new PdoOrganizationResolver($this->query);

        self::assertSame($acme, $resolver->findIdBySlug('acme'));
        self::assertNull($resolver->findIdBySlug('missing'));
    }
}
