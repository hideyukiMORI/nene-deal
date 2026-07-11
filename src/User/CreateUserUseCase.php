<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use Nene2\Http\ClockInterface;
use NeneDeal\Audit\AuditAction;
use NeneDeal\Tenancy\CurrentOrganization;
use Symfony\Component\Uid\Ulid;

final readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CurrentOrganization $organization,
        private ClockInterface $clock,
        private AuditRecorderInterface $audit,
    ) {
    }

    /** @throws EmailAlreadyTakenException */
    public function execute(CreateUserInput $input, ?string $actorUserId = null): User
    {
        $existing = $this->users->findByEmail($input->email);

        if ($existing !== null) {
            throw new EmailAlreadyTakenException($input->email);
        }

        $now = $this->clock->now()->format('Y-m-d H:i:s');
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

        $this->audit->record(new AuditEvent(
            action: AuditAction::USER_CREATED,
            entityType: 'user',
            entityId: $user->id,
            actorId: $actorUserId,
            organizationId: $user->organizationId,
            after: ['email' => $user->email, 'role' => $user->role->value, 'status' => $user->status->value],
        ));

        return $user;
    }
}
