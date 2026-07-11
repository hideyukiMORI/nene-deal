<?php

declare(strict_types=1);

namespace NeneDeal\Auth;

use NeneDeal\User\User;
use NeneDeal\User\UserRepositoryInterface;
use NeneDeal\User\UserStatus;

final readonly class GetCurrentUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    /**
     * A disabled account resolves to null (→ 401) so an already-issued token
     * stops working at the next `/me` refresh instead of surviving its full
     * TTL (#90).
     */
    public function execute(string $userId): ?User
    {
        $user = $this->users->findById($userId);

        return $user !== null && $user->status === UserStatus::Active ? $user : null;
    }
}
