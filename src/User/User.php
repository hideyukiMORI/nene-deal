<?php

declare(strict_types=1);

namespace NeneDeal\User;

/**
 * An operator account. MVP uses a single role (`operator`); RBAC is a later
 * epic. `organizationId` is the tenant the user belongs to.
 */
final readonly class User
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $email,
        public string $passwordHash,
        public string $role = 'operator',
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }
}
