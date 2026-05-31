<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class SlugAlreadyTakenExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(private ProblemDetailsResponseFactory $problemDetails)
    {
    }

    public function supports(Throwable $e): bool
    {
        return $e instanceof SlugAlreadyTakenException;
    }

    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create($request, 'stage-slug-taken', 'Conflict', 409, $e->getMessage());
    }
}
