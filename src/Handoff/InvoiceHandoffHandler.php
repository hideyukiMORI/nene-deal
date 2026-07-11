<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use Nene2\Http\JsonResponseFactory;
use NeneDeal\Auth\AuthContext;
use NeneDeal\Deal\DealField;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `POST /deals/{dealId}/invoice-handoff` — operator-confirmed handoff of a won
 * deal to NeNe Invoice (draft client + quote). Domain exceptions map to
 * 404 / 409 / 422 / 502 via their handlers.
 */
final readonly class InvoiceHandoffHandler implements RequestHandlerInterface
{
    public function __construct(
        private InvoiceHandoffUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // The optional `confirm` body and `Idempotency-Key` header are accepted
        // but not required.
        $result = $this->useCase->execute(DealField::pathId($request), AuthContext::userId($request));

        return $this->json->create($result->toArray());
    }
}
