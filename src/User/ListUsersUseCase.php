<?php

declare(strict_types=1);

namespace NeneDeal\User;

use NeneDeal\Tenancy\CurrentOrganization;

final readonly class ListUsersUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CurrentOrganization $organization,
    ) {
    }

    /** @return list<User> */
    public function execute(): array
    {
        return $this->users->findAllByOrganization($this->organization->id());
    }
}
