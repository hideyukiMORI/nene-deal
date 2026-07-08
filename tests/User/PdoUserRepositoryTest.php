<?php

declare(strict_types=1);

namespace NeneDeal\Tests\User;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeneDeal\Tests\Support\FixedClock;
use NeneDeal\User\OperatorRole;
use NeneDeal\User\PdoUserRepository;
use NeneDeal\User\User;
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

        $this->repository = new PdoUserRepository($query, new FixedClock());
    }

    public function test_finds_a_user_by_email(): void
    {
        $user = $this->repository->findByEmail('operator@nene-deal.test');
        self::assertNotNull($user);
        self::assertSame($this->userId, $user->id);
        self::assertSame(OperatorRole::Operator, $user->role);
        self::assertTrue(password_verify('password', $user->passwordHash));
    }

    public function test_finds_a_user_by_id_and_returns_null_for_unknown(): void
    {
        self::assertNotNull($this->repository->findById($this->userId));
        self::assertNull($this->repository->findByEmail('nobody@nene-deal.test'));
        self::assertNull($this->repository->findById('01MISSINGUSER0000000000AA'));
    }

    public function test_save_inserts_new_user(): void
    {
        $id = (string) new Ulid();
        $orgId = (string) new Ulid();
        $user = new User(
            id: $id,
            organizationId: $orgId,
            email: 'new@nene-deal.test',
            passwordHash: password_hash('secret', PASSWORD_DEFAULT),
            role: OperatorRole::Admin,
        );

        $this->repository->save($user);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame('new@nene-deal.test', $found->email);
        self::assertSame(OperatorRole::Admin, $found->role);
    }

    public function test_save_updates_existing_user_email_and_role(): void
    {
        $existing = $this->repository->findById($this->userId);
        self::assertNotNull($existing);

        $updated = new User(
            id: $existing->id,
            organizationId: $existing->organizationId,
            email: 'updated@nene-deal.test',
            passwordHash: $existing->passwordHash,
            role: OperatorRole::Admin,
            createdAt: $existing->createdAt,
        );

        $this->repository->save($updated);

        $found = $this->repository->findById($this->userId);
        self::assertNotNull($found);
        self::assertSame('updated@nene-deal.test', $found->email);
        self::assertSame(OperatorRole::Admin, $found->role);
    }
}
