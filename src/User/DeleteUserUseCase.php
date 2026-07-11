<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use NeneDeal\Audit\AuditAction;
use NeneDeal\Tenancy\CurrentOrganization;

final readonly class DeleteUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CurrentOrganization $organization,
        private AuditRecorderInterface $audit,
    ) {
    }

    /**
     * @throws UserNotFoundException
     * @throws CannotModifySelfException
     */
    public function execute(string $targetUserId, string $actorUserId): void
    {
        $user = $this->users->findById($targetUserId);

        if ($user === null || $user->organizationId !== $this->organization->id()) {
            throw new UserNotFoundException($targetUserId);
        }

        if ($targetUserId === $actorUserId) {
            throw new CannotModifySelfException('An admin cannot delete their own account.');
        }

        $this->users->delete($targetUserId);

        $this->audit->record(new AuditEvent(
            action: AuditAction::USER_DELETED,
            entityType: 'user',
            entityId: $user->id,
            actorId: $actorUserId,
            organizationId: $user->organizationId,
            before: ['email' => $user->email, 'role' => $user->role->value],
        ));
    }
}
