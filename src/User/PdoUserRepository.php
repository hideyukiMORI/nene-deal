<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoUserRepository implements UserRepositoryInterface
{
    private const COLUMNS = 'id, organization_id, email, password_hash, role, created_at, updated_at';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findById(string $id): ?User
    {
        $row = $this->query->fetchOne('SELECT ' . self::COLUMNS . ' FROM users WHERE id = ? LIMIT 1', [$id]);

        return $row !== null ? $this->mapRow($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->query->fetchOne('SELECT ' . self::COLUMNS . ' FROM users WHERE email = ? LIMIT 1', [$email]);

        return $row !== null ? $this->mapRow($row) : null;
    }

    /** @return list<User> */
    public function findAllByOrganization(string $organizationId): array
    {
        $rows = $this->query->fetchAll(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE organization_id = ? ORDER BY created_at ASC',
            [$organizationId],
        );

        return array_map(fn (array $row): User => $this->mapRow($row), $rows);
    }

    public function save(User $user): void
    {
        $now = date('Y-m-d H:i:s');

        $this->query->execute(
            'INSERT INTO users (id, organization_id, email, password_hash, role, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE email = VALUES(email), password_hash = VALUES(password_hash),
             role = VALUES(role), updated_at = VALUES(updated_at)',
            [
                $user->id,
                $user->organizationId,
                $user->email,
                $user->passwordHash,
                $user->role->value,
                $user->createdAt ?? $now,
                $now,
            ],
        );
    }

    public function delete(string $id): void
    {
        $this->query->execute('DELETE FROM users WHERE id = ?', [$id]);
    }

    public function emailExistsExcluding(string $email, string $excludeId): bool
    {
        $row = $this->query->fetchOne(
            'SELECT 1 FROM users WHERE email = ? AND id != ? LIMIT 1',
            [$email, $excludeId],
        );

        return $row !== null;
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): User
    {
        return new User(
            id: (string) $row['id'],
            organizationId: (string) $row['organization_id'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            role: OperatorRole::from((string) $row['role']),
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
