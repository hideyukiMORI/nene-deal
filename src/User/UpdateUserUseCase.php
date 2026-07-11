<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use NeneDeal\Audit\AuditAction;
use NeneDeal\Tenancy\CurrentOrganization;

final readonly class UpdateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CurrentOrganization $organization,
        private AuditRecorderInterface $audit,
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

        $before = [];
        $after = [];

        if ($updated->email !== $user->email) {
            $before['email'] = $user->email;
            $after['email'] = $updated->email;
        }
        if ($updated->role !== $user->role) {
            $before['role'] = $user->role->value;
            $after['role'] = $updated->role->value;
        }
        if ($updated->status !== $user->status) {
            $before['status'] = $user->status->value;
            $after['status'] = $updated->status->value;
        }

        if ($after !== []) {
            $this->audit->record(new AuditEvent(
                action: AuditAction::USER_UPDATED,
                entityType: 'user',
                entityId: $user->id,
                actorId: $actorUserId,
                organizationId: $user->organizationId,
                before: $before,
                after: $after,
            ));
        }

        return $this->users->findById($targetUserId) ?? $updated;
    }
}
