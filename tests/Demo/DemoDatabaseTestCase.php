<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Demo;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneDeal\Demo\DemoOrgHandles;
use PHPUnit\Framework\TestCase;

/**
 * Shared setup for the disposable-demo tests: an in-memory SQLite database
 * loaded with the repository-test schema, one query executor, and a fresh
 * ULID↔int handle registry.
 */
abstract class DemoDatabaseTestCase extends TestCase
{
    protected PdoDatabaseQueryExecutor $query;
    protected DemoOrgHandles $handles;

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
        $this->handles = new DemoOrgHandles();
    }

    /**
     * @param list<mixed> $parameters
     */
    protected function countRows(string $sql, array $parameters = []): int
    {
        $row = $this->query->fetchOne($sql, $parameters);

        self::assertIsArray($row);
        $value = $row[array_key_first($row)];

        return (int) $value;
    }
}
