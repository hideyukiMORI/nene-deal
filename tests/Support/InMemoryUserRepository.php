<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use NeneDeal\User\User;
use NeneDeal\User\UserRepositoryInterface;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<string, User> keyed by id */
    private array $users = [];

    public function add(User $user): void
    {
        $this->users[$user->id] = $user;
    }

    public function findById(string $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email === $email) {
                return $user;
            }
        }

        return null;
    }

    /** @return list<User> */
    public function findAllByOrganization(string $organizationId): array
    {
        return array_values(array_filter(
            $this->users,
            static fn (User $u): bool => $u->organizationId === $organizationId,
        ));
    }

    public function save(User $user): void
    {
        $this->users[$user->id] = $user;
    }

    public function delete(string $id): void
    {
        unset($this->users[$id]);
    }

    public function emailExistsExcluding(string $email, string $excludeId): bool
    {
        foreach ($this->users as $user) {
            if ($user->email === $email && $user->id !== $excludeId) {
                return true;
            }
        }

        return false;
    }
}
