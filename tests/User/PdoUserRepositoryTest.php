<?php

declare(strict_types=1);

namespace NeneDeal\Tests\User;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneDeal\User\PdoUserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class PdoUserRepositoryTest extends TestCase
{
    private PdoUserRepository $repository;
    private string $userId;

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
        $this->userId = (string) new Ulid();
        $query->execute(
            'INSERT INTO users (id, organization_id, email, password_hash, role, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $this->userId,
                (string) new Ulid(),
                'operator@nene-deal.test',
                password_hash('password', PASSWORD_DEFAULT),
                'operator',
                '2026-05-31 00:00:00',
                '2026-05-31 00:00:00',
            ],
        );

        $this->repository = new PdoUserRepository($query);
    }

    public function test_finds_a_user_by_email(): void
    {
        $user = $this->repository->findByEmail('operator@nene-deal.test');
        self::assertNotNull($user);
        self::assertSame($this->userId, $user->id);
        self::assertSame('operator', $user->role);
        self::assertTrue(password_verify('password', $user->passwordHash));
    }

    public function test_finds_a_user_by_id_and_returns_null_for_unknown(): void
    {
        self::assertNotNull($this->repository->findById($this->userId));
        self::assertNull($this->repository->findByEmail('nobody@nene-deal.test'));
        self::assertNull($this->repository->findById('01MISSINGUSER0000000000AA'));
    }
}
