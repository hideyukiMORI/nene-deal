<?php

declare(strict_types=1);

namespace NeneDeal\User;

/**
 * An operator account scoped to one organization.
 * `role` controls access: `admin` can manage users; `operator` is deal-only.
 * `status` gates authentication: only `active` accounts can log in (#90).
 */
final readonly class User
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $email,
        public string $passwordHash,
        public OperatorRole $role = OperatorRole::Operator,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public UserStatus $status = UserStatus::Active,
    ) {
    }
}
