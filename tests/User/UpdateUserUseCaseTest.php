<?php

declare(strict_types=1);

namespace NeneDeal\Tests\User;

use NeneDeal\Tests\Support\InMemoryUserRepository;
use NeneDeal\Tests\Support\StubCurrentOrganization;
use NeneDeal\User\CannotModifySelfException;
use NeneDeal\User\EmailAlreadyTakenException;
use NeneDeal\User\OperatorRole;
use NeneDeal\User\UpdateUserInput;
use NeneDeal\User\UpdateUserUseCase;
use NeneDeal\User\User;
use NeneDeal\User\UserNotFoundException;
use NeneDeal\User\UserStatus;
use PHPUnit\Framework\TestCase;

final class UpdateUserUseCaseTest extends TestCase
{
    private const ORG_ID = '01ORG00000000000000000000A';
    private const ADMIN_ID = '01USERADMIN000000000000001';
    private const OPERATOR_ID = '01USEROPERATOR0000000000A1';

    private InMemoryUserRepository $users;
    private UpdateUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->users->add(new User(self::ADMIN_ID, self::ORG_ID, 'admin@example.com', 'hash', OperatorRole::Admin));
        $this->users->add(new User(self::OPERATOR_ID, self::ORG_ID, 'op@example.com', 'hash', OperatorRole::Operator));
        $this->useCase = new UpdateUserUseCase($this->users, new StubCurrentOrganization(self::ORG_ID));
    }

    public function test_updates_role(): void
    {
        $updated = $this->useCase->execute(self::OPERATOR_ID, self::ADMIN_ID, new UpdateUserInput(role: OperatorRole::Admin));

        self::assertSame(OperatorRole::Admin, $updated->role);
    }

    public function test_throws_when_changing_own_role(): void
    {
        $this->expectException(CannotModifySelfException::class);
        $this->useCase->execute(self::ADMIN_ID, self::ADMIN_ID, new UpdateUserInput(role: OperatorRole::Operator));
    }

    public function test_throws_when_email_already_taken(): void
    {
        $this->expectException(EmailAlreadyTakenException::class);
        $this->useCase->execute(self::OPERATOR_ID, self::ADMIN_ID, new UpdateUserInput(email: 'admin@example.com'));
    }

    public function test_throws_when_user_not_found(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->useCase->execute('01UNKNOWNUSER00000000000AA', self::ADMIN_ID, new UpdateUserInput(role: OperatorRole::Operator));
    }

    public function test_disables_an_account(): void
    {
        $updated = $this->useCase->execute(self::OPERATOR_ID, self::ADMIN_ID, new UpdateUserInput(status: UserStatus::Disabled));

        self::assertSame(UserStatus::Disabled, $updated->status);
    }

    public function test_re_enables_a_disabled_account(): void
    {
        $this->useCase->execute(self::OPERATOR_ID, self::ADMIN_ID, new UpdateUserInput(status: UserStatus::Disabled));
        $updated = $this->useCase->execute(self::OPERATOR_ID, self::ADMIN_ID, new UpdateUserInput(status: UserStatus::Active));

        self::assertSame(UserStatus::Active, $updated->status);
    }

    public function test_throws_when_disabling_own_account(): void
    {
        $this->expectException(CannotModifySelfException::class);
        $this->useCase->execute(self::ADMIN_ID, self::ADMIN_ID, new UpdateUserInput(status: UserStatus::Disabled));
    }
}
