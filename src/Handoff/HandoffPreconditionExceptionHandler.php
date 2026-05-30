<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class HandoffPreconditionExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof HandoffPreconditionException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $detail = $exception->getMessage() !== '' ? $exception->getMessage() : 'Handoff preconditions are not met.';

        return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, $detail);
    }
}
