<?php

declare(strict_types=1);

namespace NeneDeal\User;

use NeneDeal\Tenancy\CurrentOrganization;

final readonly class UpdateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CurrentOrganization $organization,
    ) {
    }

    /**
     * @throws UserNotFoundException
     * @throws EmailAlreadyTakenException
     * @throws CannotModifySelfException
     */
    public function execute(string $targetUserId, string $actorUserId, UpdateUserInput $input): User
    {
        $user = $this->users->findById($targetUserId);

        if ($user === null || $user->organizationId !== $this->organization->id()) {
            throw new UserNotFoundException($targetUserId);
        }

        if ($input->role !== null && $targetUserId === $actorUserId) {
            throw new CannotModifySelfException('An admin cannot change their own role.');
        }

        if ($input->status !== null && $targetUserId === $actorUserId) {
            throw new CannotModifySelfException('An admin cannot change their own account status.');
        }

        $email = $input->email ?? $user->email;

        if ($input->email !== null && $this->users->emailExistsExcluding($input->email, $targetUserId)) {
            throw new EmailAlreadyTakenException($input->email);
        }

        $updated = new User(
            id: $user->id,
            organizationId: $user->organizationId,
            email: $email,
            passwordHash: $user->passwordHash,
            role: $input->role ?? $user->role,
            createdAt: $user->createdAt,
            status: $input->status ?? $user->status,
        );

        $this->users->save($updated);

        return $this->users->findById($targetUserId) ?? $updated;
    }
}
