<?php

declare(strict_types=1);

namespace NeneDeal\Auth;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class TooManyLoginAttemptsExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof TooManyLoginAttemptsException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        assert($exception instanceof TooManyLoginAttemptsException);

        return $this->problemDetails->create(
            $request,
            'too-many-login-attempts',
            'Too Many Login Attempts',
            429,
            'Too many failed login attempts. Try again later.',
            ['retry_after_seconds' => $exception->retryAfterSeconds],
        );
    }
}
