<?php

declare(strict_types=1);

namespace NeneDeal\User;

/**
 * Persistence for operator accounts.
 *
 * Identity lookups (findById, findByEmail) are NOT scoped to the request
 * organization — they run on pre-auth paths (login, token resolution).
 * Management operations (findAllByOrg, save, delete) ARE org-scoped.
 */
interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    /** @return list<User> */
    public function findAllByOrganization(string $organizationId): array;

    public function save(User $user): void;

    public function delete(string $id): void;

    public function emailExistsExcluding(string $email, string $excludeId): bool;
}
