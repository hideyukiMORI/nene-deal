<?php

declare(strict_types=1);

namespace NeneDeal\Tests\User;

use NeneDeal\Tests\Support\FixedClock;
use NeneDeal\Tests\Support\InMemoryUserRepository;
use NeneDeal\Tests\Support\StubCurrentOrganization;
use NeneDeal\User\CreateUserInput;
use NeneDeal\User\CreateUserUseCase;
use NeneDeal\User\EmailAlreadyTakenException;
use NeneDeal\User\OperatorRole;
use NeneDeal\User\User;
use PHPUnit\Framework\TestCase;

final class CreateUserUseCaseTest extends TestCase
{
    private const ORG_ID = '01ORG00000000000000000000A';

    private InMemoryUserRepository $users;
    private CreateUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->useCase = new CreateUserUseCase($this->users, new StubCurrentOrganization(self::ORG_ID), new FixedClock());
    }

    public function test_creates_a_user_with_hashed_password(): void
    {
        $user = $this->useCase->execute(new CreateUserInput(
            email: 'new@nene-deal.test',
            password: 'securepassword',
            role: OperatorRole::Operator,
        ));

        self::assertSame('new@nene-deal.test', $user->email);
        self::assertSame(OperatorRole::Operator, $user->role);
        self::assertSame(self::ORG_ID, $user->organizationId);
        self::assertTrue(password_verify('securepassword', $user->passwordHash));
        // Timestamps come from the injected fixed clock, not the wall clock.
        self::assertSame('2026-06-01 10:00:00', $user->createdAt);
        self::assertSame('2026-06-01 10:00:00', $user->updatedAt);
    }

    public function test_throws_when_email_already_taken(): void
    {
        $this->users->add(new User(
            id: '01USEREXISTING00000000001A',
            organizationId: self::ORG_ID,
            email: 'taken@nene-deal.test',
            passwordHash: password_hash('pass', PASSWORD_DEFAULT),
        ));

        $this->expectException(EmailAlreadyTakenException::class);
        $this->useCase->execute(new CreateUserInput('taken@nene-deal.test', 'password123', OperatorRole::Operator));
    }
}
