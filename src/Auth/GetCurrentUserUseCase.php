<?php

declare(strict_types=1);

namespace NeneDeal\Auth;

use NeneDeal\User\User;
use NeneDeal\User\UserRepositoryInterface;

final readonly class GetCurrentUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(string $userId): ?User
    {
        return $this->users->findById($userId);
    }
}
