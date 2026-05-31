<?php

declare(strict_types=1);

namespace NeneDeal\User;

use NeneDeal\Tenancy\CurrentOrganization;
use Symfony\Component\Uid\Ulid;

final readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CurrentOrganization $organization,
    ) {
    }

    /** @throws EmailAlreadyTakenException */
    public function execute(CreateUserInput $input): User
    {
        $existing = $this->users->findByEmail($input->email);

        if ($existing !== null) {
            throw new EmailAlreadyTakenException($input->email);
        }

        $now = date('Y-m-d H:i:s');
        $user = new User(
            id: (string) new Ulid(),
            organizationId: $this->organization->id(),
            email: $input->email,
            passwordHash: password_hash($input->password, PASSWORD_DEFAULT),
            role: $input->role,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->users->save($user);

        return $user;
    }
}
