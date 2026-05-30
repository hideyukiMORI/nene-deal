<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class AlreadyHandedOffExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof AlreadyHandedOffException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $extensions = $exception instanceof AlreadyHandedOffException
            ? ['invoice_client_id' => $exception->invoiceClientId, 'invoice_quote_id' => $exception->invoiceQuoteId]
            : [];

        return $this->problemDetails->create(
            $request,
            'invoice-handoff-already-linked',
            'Deal already handed off',
            409,
            'This deal is already linked to an Invoice quote.',
            $extensions,
        );
    }
}
