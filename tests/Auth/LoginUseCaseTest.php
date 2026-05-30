<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use NeneDeal\Auth\InvalidCredentialsException;
use NeneDeal\Auth\LoginInput;
use NeneDeal\Auth\LoginUseCase;
use NeneDeal\Tests\Support\InMemoryUserRepository;
use NeneDeal\User\User;
use PHPUnit\Framework\TestCase;

final class LoginUseCaseTest extends TestCase
{
    private InMemoryUserRepository $users;
    private LocalBearerTokenVerifier $tokens;
    private LoginUseCase $useCase;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->users->add(new User(
            id: '01USEROPERATOR0000000000AA',
            organizationId: '01ORG00000000000000000000A',
            email: 'operator@nene-deal.test',
            passwordHash: password_hash('password', PASSWORD_DEFAULT),
            role: 'operator',
        ));

        $this->tokens = new LocalBearerTokenVerifier('test-secret');
        $this->useCase = new LoginUseCase($this->users, $this->tokens);
    }

    public function test_issues_a_verifiable_token_for_valid_credentials(): void
    {
        $output = $this->useCase->execute(new LoginInput('operator@nene-deal.test', 'password'));

        $claims = $this->tokens->verify($output->token);
        self::assertSame('01USEROPERATOR0000000000AA', $claims['sub']);
        self::assertSame('operator', $claims['role']);
        self::assertSame('01ORG00000000000000000000A', $claims['org']);
    }

    public function test_rejects_a_wrong_password(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->useCase->execute(new LoginInput('operator@nene-deal.test', 'wrong'));
    }

    public function test_rejects_an_unknown_email(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->useCase->execute(new LoginInput('nobody@nene-deal.test', 'password'));
    }
}
