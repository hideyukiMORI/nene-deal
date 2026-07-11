<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Auth\LoginHandler;
use NeneDeal\Auth\LoginThrottleInterface;
use NeneDeal\Auth\LoginUseCase;
use NeneDeal\Auth\TooManyLoginAttemptsException;
use NeneDeal\Tests\Support\FixedClock;
use NeneDeal\Tests\Support\InMemoryUserRepository;
use NeneDeal\Tests\Support\RecordingAuditRecorder;
use NeneDeal\User\OperatorRole;
use NeneDeal\User\User;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/** Interaction-recording throttle for handler tests. */
final class SpyLoginThrottle implements LoginThrottleInterface
{
    /** @var list<string> */
    public array $failures = [];

    /** @var list<string> */
    public array $cleared = [];

    public int $lockedSeconds = 0;

    public function secondsUntilUnlocked(string $identifier): int
    {
        return $this->lockedSeconds;
    }

    public function recordFailure(string $identifier): void
    {
        $this->failures[] = $identifier;
    }

    public function clear(string $identifier): void
    {
        $this->cleared[] = $identifier;
    }
}

final class LoginHandlerTest extends TestCase
{
    private Psr17Factory $psr17;
    private SpyLoginThrottle $throttle;
    private LoginHandler $handler;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();

        $users = new InMemoryUserRepository();
        $users->add(new User(
            id: '01USEROPERATOR0000000000AA',
            organizationId: '01ORG00000000000000000000A',
            email: 'operator@nene-deal.test',
            passwordHash: password_hash('password', PASSWORD_DEFAULT),
            role: OperatorRole::Operator,
        ));

        $clock = new FixedClock();
        $useCase = new LoginUseCase($users, new LocalBearerTokenVerifier('test-secret', $clock), $clock, new RecordingAuditRecorder());

        $this->throttle = new SpyLoginThrottle();
        $this->handler = new LoginHandler(
            $useCase,
            new JsonResponseFactory($this->psr17, $this->psr17),
            $this->throttle,
        );
    }

    private function loginRequest(string $email, string $password, string $ip = '203.0.113.9'): ServerRequestInterface
    {
        $body = json_encode(['email' => $email, 'password' => $password]);
        self::assertIsString($body);

        return $this->psr17->createServerRequest('POST', '/api/v1/auth/login', ['REMOTE_ADDR' => $ip])
            ->withBody($this->psr17->createStream($body));
    }

    public function test_successful_login_clears_the_throttle_counter(): void
    {
        $response = $this->handler->handle($this->loginRequest('operator@nene-deal.test', 'password'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['operator@nene-deal.test|203.0.113.9'], $this->throttle->cleared);
        self::assertSame([], $this->throttle->failures);
    }

    public function test_failed_login_records_a_throttle_failure(): void
    {
        try {
            $this->handler->handle($this->loginRequest('operator@nene-deal.test', 'wrong'));
            self::fail('Expected InvalidCredentialsException.');
        } catch (\NeneDeal\Auth\InvalidCredentialsException) {
            // Expected — rethrown for the domain exception handler.
        }

        self::assertSame(['operator@nene-deal.test|203.0.113.9'], $this->throttle->failures);
        self::assertSame([], $this->throttle->cleared);
    }

    public function test_locked_identifier_is_rejected_with_retry_seconds_before_credential_check(): void
    {
        $this->throttle->lockedSeconds = 321;

        try {
            $this->handler->handle($this->loginRequest('operator@nene-deal.test', 'password'));
            self::fail('Expected TooManyLoginAttemptsException.');
        } catch (TooManyLoginAttemptsException $e) {
            self::assertSame(321, $e->retryAfterSeconds);
        }

        // No failure recorded and no counter cleared while locked.
        self::assertSame([], $this->throttle->failures);
        self::assertSame([], $this->throttle->cleared);
    }

    public function test_identifier_lowercases_the_email(): void
    {
        try {
            $this->handler->handle($this->loginRequest('Operator@NeNe-Deal.test', 'wrong'));
            self::fail('Expected InvalidCredentialsException.');
        } catch (\NeneDeal\Auth\InvalidCredentialsException) {
            // Expected.
        }

        // Case variations of the same email map to one throttle identifier.
        self::assertSame(['operator@nene-deal.test|203.0.113.9'], $this->throttle->failures);
    }
}
