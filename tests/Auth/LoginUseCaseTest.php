<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use NeneDeal\Auth\InvalidCredentialsException;
use NeneDeal\Auth\LoginInput;
use NeneDeal\Auth\LoginUseCase;
use NeneDeal\Tests\Support\FixedClock;
use NeneDeal\Tests\Support\InMemoryUserRepository;
use NeneDeal\Tests\Support\RecordingAuditRecorder;
use NeneDeal\User\OperatorRole;
use NeneDeal\User\User;
use NeneDeal\User\UserStatus;
use PHPUnit\Framework\TestCase;

final class LoginUseCaseTest extends TestCase
{
    private InMemoryUserRepository $users;
    private LocalBearerTokenVerifier $tokens;
    private FixedClock $clock;
    private LoginUseCase $useCase;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->users->add(new User(
            id: '01USEROPERATOR0000000000AA',
            organizationId: '01ORG00000000000000000000A',
            email: 'operator@nene-deal.test',
            passwordHash: password_hash('password', PASSWORD_DEFAULT),
            role: OperatorRole::Operator,
        ));

        // One fixed time source shared by the issuer/verifier and the use case,
        // so iat/exp issuance and exp verification are deterministic.
        $this->clock = new FixedClock();
        $this->tokens = new LocalBearerTokenVerifier('test-secret', $this->clock);
        $this->useCase = new LoginUseCase($this->users, $this->tokens, $this->clock, new RecordingAuditRecorder());
    }

    public function test_issues_a_verifiable_token_for_valid_credentials(): void
    {
        $output = $this->useCase->execute(new LoginInput('operator@nene-deal.test', 'password'));

        $claims = $this->tokens->verify($output->token);
        self::assertSame('01USEROPERATOR0000000000AA', $claims['sub']);
        self::assertSame('operator', $claims['role']);
        self::assertSame('01ORG00000000000000000000A', $claims['org']);
    }

    public function test_token_claims_are_deterministic_with_a_fixed_clock(): void
    {
        $output = $this->useCase->execute(new LoginInput('operator@nene-deal.test', 'password'));

        $issuedAt = $this->clock->now()->getTimestamp();
        $claims = $this->tokens->verify($output->token);
        self::assertSame($issuedAt, $claims['iat']);
        self::assertSame($issuedAt + 3600, $claims['exp']);
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

    public function test_rejects_a_disabled_account_even_with_the_correct_password(): void
    {
        $this->users->add(new User(
            id: '01USERDISABLED0000000000AA',
            organizationId: '01ORG00000000000000000000A',
            email: 'disabled@nene-deal.test',
            passwordHash: password_hash('password', PASSWORD_DEFAULT),
            role: OperatorRole::Operator,
            status: UserStatus::Disabled,
        ));

        $this->expectException(InvalidCredentialsException::class);
        $this->useCase->execute(new LoginInput('disabled@nene-deal.test', 'password'));
    }
}
