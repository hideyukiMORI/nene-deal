<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class UnknownStageExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof UnknownStageException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $detail = $exception instanceof UnknownStageException
            ? sprintf('Stage "%s" does not exist in this organization.', $exception->stageRef)
            : 'The requested stage does not exist.';

        return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, $detail);
    }
}
