<?php

declare(strict_types=1);

namespace NeneDeal\Tests\User;

use NeneDeal\Tests\Support\InMemoryUserRepository;
use NeneDeal\Tests\Support\StubCurrentOrganization;
use NeneDeal\User\CannotModifySelfException;
use NeneDeal\User\DeleteUserUseCase;
use NeneDeal\User\OperatorRole;
use NeneDeal\User\User;
use NeneDeal\User\UserNotFoundException;
use PHPUnit\Framework\TestCase;

final class DeleteUserUseCaseTest extends TestCase
{
    private const ORG_ID = '01ORG00000000000000000000A';
    private const ADMIN_ID = '01USERADMIN000000000000001';
    private const OPERATOR_ID = '01USEROPERATOR0000000000A1';

    private InMemoryUserRepository $users;
    private DeleteUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->users->add(new User(self::ADMIN_ID, self::ORG_ID, 'admin@example.com', 'hash', OperatorRole::Admin));
        $this->users->add(new User(self::OPERATOR_ID, self::ORG_ID, 'op@example.com', 'hash', OperatorRole::Operator));
        $this->useCase = new DeleteUserUseCase($this->users, new StubCurrentOrganization(self::ORG_ID));
    }

    public function test_deletes_a_user(): void
    {
        $this->useCase->execute(self::OPERATOR_ID, self::ADMIN_ID);

        self::assertNull($this->users->findById(self::OPERATOR_ID));
    }

    public function test_throws_when_deleting_self(): void
    {
        $this->expectException(CannotModifySelfException::class);
        $this->useCase->execute(self::ADMIN_ID, self::ADMIN_ID);
    }

    public function test_throws_when_user_not_found(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->useCase->execute('01UNKNOWNUSER00000000000AA', self::ADMIN_ID);
    }
}
