<?php

declare(strict_types=1);

namespace NeneDeal\User;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class EmailAlreadyTakenExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(private ProblemDetailsResponseFactory $problemDetails)
    {
    }

    public function supports(Throwable $e): bool
    {
        return $e instanceof EmailAlreadyTakenException;
    }

    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'email-already-taken', 'Conflict', 409, $e->getMessage());
    }
}
